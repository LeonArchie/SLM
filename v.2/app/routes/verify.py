from flask import Blueprint, request, jsonify, current_app
from services.token_service import TokenService
from services.logger_service import LoggerService
import jwt

# Инициализация логгера для модуля проверки токена
logger = LoggerService.get_logger('app.auth.verify')

# Создание Blueprint для маршрута проверки токена
verify_bp = Blueprint('verify', __name__)

@verify_bp.route('/auth/verify', methods=['POST'])
def verify_token():
    """Проверка валидности JWT токена"""
    # Логирование начала обработки запроса на проверку токена
    logger.info("Получен запрос на проверку токена")
    
    try:
        # Получаем данные из тела запроса в формате JSON
        data = request.get_json()
        if not data or 'token' not in data:
            # Логирование предупреждения о том, что токен не предоставлен
            logger.warning("Проверка токена не удалась - токен не предоставлен")
            return jsonify({"error": "Токен обязателен"}), 400

        # Проверяем токен на валидность с помощью сервиса
        payload = TokenService.verify_token(data['token'])
        
        # Логирование успешной проверки токена
        logger.info(f"Токен успешно проверен для user_id={payload['user_id']}")
        return jsonify({
            "valid": True,               # Токен валиден
            "user_id": payload['user_id'],  # ID пользователя из токена
            "token_type": payload['type'],  # Тип токена (например, access или refresh)
            "expires_at": payload['exp']    # Время истечения токена
        })
        
    except jwt.ExpiredSignatureError:
        # Логирование предупреждения о просроченном токене
        logger.warning("Проверка токена не удалась - токен просрочен")
        return jsonify({
            "valid": False,              # Токен недействителен
            "error": "Токен просрочен",
            "should_refresh": True       # Флаг, указывающий, что токен можно обновить
        }), 401
        
    except jwt.InvalidTokenError as e:
        # Логирование предупреждения о недействительном токене
        logger.warning(f"Проверка токена не удалась - недействительный токен: {str(e)}")
        return jsonify({
            "valid": False,              # Токен недействителен
            "error": "Недействительный токен",
            "should_refresh": False      # Флаг, указывающий, что токен нельзя обновить
        }), 401
        
    except Exception as e:
        # Логирование общей ошибки при проверке токена
        logger.error(f"Ошибка при проверке токена: {str(e)}", exc_info=True)
        return jsonify({
            "error": "Внутренняя ошибка сервера при проверке токена"
        }), 500