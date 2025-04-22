# Импорт необходимых библиотек
import jwt  # Для работы с JWT токенами
from flask import Blueprint, request, jsonify  # Компоненты Flask для создания API
from services.token_service import TokenService  # Сервис работы с токенами
from services.privileges_user_view_service import PrivilegesService  # Сервис работы с привилегиями
from services.logger_service import LoggerService  # Сервис логирования

# Инициализация логгера для модуля привилегий
logger = LoggerService.get_logger('app.privileges')

# Создание Blueprint для группировки роутов привилегий
privileges_bp = Blueprint('privileges', __name__)

@privileges_bp.route('/privileges/user_view', methods=['POST'])
def get_user_privileges():
    """
    Роут для получения списка привилегий пользователя
    Требует валидный access_token и user_id в теле запроса
    Возвращает JSON с массивом привилегий пользователя
    """
    logger.info("Получен запрос на получение привилегий пользователя")
    
    try:
        # Получаем и проверяем входные данные
        data = request.get_json()
        if not data or 'access_token' not in data or 'user_id' not in data:
            logger.warning("Невалидный запрос - отсутствуют обязательные поля")
            return jsonify({"error": "Требуются access_token и user_id"}), 400

        # Верификация токена доступа
        try:
            # Декодируем и проверяем токен
            payload = TokenService.verify_token(data['access_token'])
                
        except jwt.ExpiredSignatureError:  # Истек срок действия токена
            logger.warning("Токен просрочен")
            return jsonify({
                "error": "Токен просрочен",
                "should_refresh": True  # Флаг, указывающий на необходимость обновить токен
            }), 401
        except jwt.InvalidTokenError:  # Невалидный токен
            logger.warning("Невалидный токен")
            return jsonify({"error": "Невалидный токен"}), 401

        # Получаем привилегии пользователя из сервиса
        privileges = PrivilegesService.get_user_privileges(data['user_id'])
        
        logger.info(f"Успешно получены привилегии для пользователя {data['user_id']}")
        return jsonify({
            "privileges": privileges  # Возвращаем массив привилегий
        })
        
    except Exception as e:
        # Обработка непредвиденных ошибок
        logger.error(f"Ошибка при получении привилегий пользователя: {str(e)}", exc_info=True)
        return jsonify({"error": "Внутренняя ошибка сервера"}), 500