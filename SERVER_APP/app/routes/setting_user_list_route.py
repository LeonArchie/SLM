from flask import Blueprint, request, jsonify
from services.setting_user_list_service import UserListService
from services.logger_service import LoggerService
from services.token_service import TokenService

# Инициализация логгера для модуля получения списка пользователей
logger = LoggerService.get_logger('app.user_list')

# Создание Blueprint для маршрута получения списка пользователей
user_list_bp = Blueprint('user_list', __name__)

@user_list_bp.route('/setting/user/list', methods=['POST'])
def get_user_list():
    """Endpoint для получения списка пользователей с подробной информацией"""
    # Логирование начала обработки запроса на получение списка пользователей
    logger.info("Получен запрос на получение списка пользователей")
    
    try:
        # Получаем данные из тела запроса в формате JSON
        data = request.get_json()
        if not data or 'user_id' not in data or 'access_token' not in data:
            # Логирование предупреждения о том, что запрос некорректен (отсутствуют обязательные поля)
            logger.warning("Некорректный запрос - отсутствуют обязательные поля")
            return jsonify({"error": "Требуются user_id и access_token"}), 400

        user_id = data['user_id']
        access_token = data['access_token']

        # Проверяем токен и соответствие user_id
        try:
            payload = TokenService.verify_token(access_token)
            if payload['user_id'] != user_id:
                # Логирование предупреждения о несоответствии user_id в токене и запросе
                logger.warning(f"Несоответствие user_id в токене: {payload['user_id']} != {user_id}")
                return jsonify({"error": "Недействительный токен для данного пользователя"}), 403
        except Exception as e:
            # Логирование предупреждения о неудачной проверке токена
            logger.warning(f"Проверка токена не удалась: {str(e)}")
            return jsonify({"error": "Проверка токена не удалась"}), 401

        # Получаем список пользователей из сервиса
        users = UserListService.get_user_list()
        
        # Логирование успешного получения списка пользователей
        logger.info(f"Успешно получено {len(users)} пользователей")
        return jsonify({
            "status": "success",
            "users": users  # Возвращаем список пользователей
        })

    except Exception as e:
        # Логирование общей ошибки при обработке запроса
        logger.error(f"Ошибка в endpoint списка пользователей: {str(e)}", exc_info=True)
        return jsonify({
            "error": "Внутренняя ошибка сервера при обработке списка пользователей"
        }), 500