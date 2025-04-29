from flask import Blueprint, request, jsonify
from services.logger_service import LoggerService
from services.connect_db_service import DatabaseService
from services.setting_user_create_service import UserCreateService

# Инициализация логгера для модуля создания пользователей
logger = LoggerService.get_logger('app.user.create.route')

# Создание Blueprint для маршрута создания пользователей
user_create_bp = Blueprint('user_create', __name__)

@user_create_bp.route('/setting/user/create', methods=['POST'])
def create_user():
    """Endpoint для создания новых пользователей"""
    # Логирование начала обработки запроса на создание пользователя
    logger.info("Получен запрос на создание пользователя")

    try:
        # Получение данных из тела запроса в формате JSON
        data = request.get_json()
        if not data:
            # Логирование предупреждения о том, что данные не предоставлены
            logger.warning("Данные не предоставлены в запросе")
            return jsonify({"error": "Данные не предоставлены"}), 400

        # Проверка наличия обязательных полей в данных запроса
        required_fields = ['access_token', 'user_id', 'userlogin', 'full_name', 'password_hash']
        if not all(field in data for field in required_fields):
            # Логирование предупреждения о недостающих обязательных полях
            logger.warning("Отсутствуют обязательные поля")
            return jsonify({"error": "Отсутствуют обязательные поля"}), 400

        # Формирование данных пользователя для дальнейшей обработки
        user_data = {
            'userlogin': data['userlogin'],  # Логин пользователя
            'full_name': data['full_name'],  # Полное имя пользователя
            'user_off_email': data.get('user_off_email'),  # Email пользователя (необязательное поле)
            'password_hash': data['password_hash']  # Хэш пароля пользователя
        }

        # Вызов сервиса для создания пользователя
        result = UserCreateService.create_user(
            data['access_token'],  # Токен доступа
            data['user_id'],       # ID пользователя
            user_data              # Данные пользователя
        )

        # Если результат содержит ошибку, возвращаем её клиенту
        if isinstance(result, tuple) and len(result) == 2 and isinstance(result[0], dict) and 'error' in result[0]:
            return jsonify(result[0]), result[1]
        elif isinstance(result, dict) and 'error' in result:
            return jsonify(result), 401

        # Сохранение данных пользователя в базу данных
        try:
            with DatabaseService.get_connection() as conn:  # Получение соединения с базой данных
                with conn.cursor() as cur:  # Создание курсора для выполнения SQL-запросов
                    cur.execute(
                        """
                        INSERT INTO users 
                        (userid, userlogin, full_name, user_off_email, password_hash, active, add_ldap, regtimes)
                        VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
                        """,
                        (
                            result['userid'],         # ID пользователя
                            result['userlogin'],      # Логин пользователя
                            result['full_name'],      # Полное имя пользователя
                            result['user_off_email'],          # Email пользователя
                            result['password_hash'],  # Хэш пароля
                            result['active'],         # Статус активности
                            result['add_ldap'],       # Флаг добавления через LDAP
                            result['regtimes'].strftime('%Y-%m-%d %H:%M:%S')      # Время регистрации
                        )
                    )
                    conn.commit()  # Подтверждение транзакции
                    # Логирование успешного создания пользователя
                    logger.info(f"Пользователь {result['userid']} успешно создан")
                    return jsonify({
                        "success": True,
                        "user_id": result['userid']  # Возвращение ID созданного пользователя
                    }), 201

        except Exception as e:
            # Логирование ошибки при работе с базой данных
            logger.error(f"Ошибка базы данных при создании пользователя: {str(e)}", exc_info=True)
            return jsonify({"error": "Ошибка базы данных"}), 500

    except Exception as e:
        # Логирование общей ошибки при создании пользователя
        logger.error(f"Ошибка при создании пользователя: {str(e)}", exc_info=True)
        return jsonify({"error": "Внутренняя ошибка сервера"}), 500