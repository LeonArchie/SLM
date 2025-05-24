from flask import Blueprint, request, jsonify
from services.logger_service import LoggerService
from services.connect_db_service import DatabaseService
from services.setting_user_create_service import UserCreateService
from services.guid_generate_service import GuidGenerateService

# Инициализация логгера
logger = LoggerService.get_logger('app.user.create.route')

# Константа для привилегии по умолчанию
DEFAULT_PRIVILEGE_ID = '3fda4364-74ff-4ea7-a4d4-5cca300758a2'

user_create_bp = Blueprint('user_create', __name__)

@user_create_bp.route('/setting/user/create', methods=['POST'])
def create_user():
    """Endpoint для создания новых пользователей"""
    logger.info("Получен запрос на создание пользователя")

    try:
        data = request.get_json()
        if not data:
            logger.warning("Данные не предоставлены в запросе")
            return jsonify({"error": "Данные не предоставлены"}), 400

        required_fields = ['access_token', 'user_id', 'userlogin', 'full_name', 'password_hash']
        if not all(field in data for field in required_fields):
            logger.warning("Отсутствуют обязательные поля")
            return jsonify({"error": "Отсутствуют обязательные поля"}), 400

        user_data = {
            'userlogin': data['userlogin'],
            'full_name': data['full_name'],
            'user_off_email': data.get('user_off_email'),
            'password_hash': data['password_hash']
        }

        result = UserCreateService.create_user(
            data['access_token'],
            data['user_id'],
            user_data
        )

        if isinstance(result, tuple) and len(result) == 2 and isinstance(result[0], dict) and 'error' in result[0]:
            return jsonify(result[0]), result[1]
        elif isinstance(result, dict) and 'error' in result:
            return jsonify(result), 401

        # Первая транзакция - создание пользователя
        try:
            with DatabaseService.get_connection() as conn:
                with conn.cursor() as cur:
                    cur.execute(
                        """INSERT INTO users 
                        (userid, userlogin, full_name, user_off_email, password_hash, 
                         active, add_ldap, regtimes, reg_user_id)
                        VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)""",
                        (
                            result['userid'],
                            result['userlogin'],
                            result['full_name'],
                            result['user_off_email'],
                            result['password_hash'],
                            result['active'],
                            result['add_ldap'],
                            result['regtimes'].strftime('%Y-%m-%d %H:%M:%S'),
                            result['reg_user_id']
                        )
                    )
                    conn.commit()
                    logger.info(f"Пользователь {result['userid']} успешно создан")
        except Exception as e:
            logger.error(f"Ошибка базы данных при создании пользователя: {str(e)}", exc_info=True)
            return jsonify({"error": "Ошибка базы данных"}), 500

        # Вторая транзакция - добавление привилегии (отдельная транзакция)
        privilege_added = False
        try:
            with DatabaseService.get_connection() as conn:
                with conn.cursor() as cur:
                    privilege_id = GuidGenerateService.generate_guid()
                    cur.execute(
                        """INSERT INTO privileges 
                        (id, userid, id_privileges) 
                        VALUES (%s, %s, %s)""",
                        (privilege_id, result['userid'], DEFAULT_PRIVILEGE_ID)
                    )
                    conn.commit()
                    privilege_added = True
                    logger.info(f"Привилегия добавлена для пользователя {result['userid']}")
        except Exception as e:
            logger.error(f"Ошибка при добавлении привилегии: {str(e)}", exc_info=True)

        response = {
            "success": True,
            "user_id": result['userid'],
            "privilege_added": privilege_added
        }
        if not privilege_added:
            response["warning"] = "Пользователь создан, но не удалось добавить привилегию"

        return jsonify(response), 201

    except Exception as e:
        logger.error(f"Ошибка при создании пользователя: {str(e)}", exc_info=True)
        return jsonify({"error": "Внутренняя ошибка сервера"}), 500