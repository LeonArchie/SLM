from flask import Blueprint, request, jsonify
from services.token_service import TokenService
from services.setting_user_data_service import UserDataService
from services.logger_service import LoggerService
import jwt

# Инициализация логгера для модуля получения данных пользователя
logger = LoggerService.get_logger('app.user.data')

# Создание Blueprint для маршрута получения данных пользователя
user_data_bp = Blueprint('user_data', __name__)

@user_data_bp.route('/setting/user/data', methods=['POST'])
def get_user_data():
    """Получение данных пользователя с проверкой токена и user_id"""
    # Логирование начала обработки запроса на получение данных пользователя
    logger.info("Получен запрос на получение данных пользователя с проверкой токена и user_id")
    
    try:
        # Получаем данные из тела запроса в формате JSON
        data = request.get_json()
        if not data or 'access_token' not in data or 'user_id' not in data:
            # Логирование предупреждения о том, что запрос некорректен (отсутствуют обязательные поля)
            logger.warning("Некорректный запрос - необходимы токен и user_id")
            return jsonify({"error": "Требуются токен и user_id"}), 400

        # Проверяем токен на валидность
        payload = TokenService.verify_token(data['access_token'])
        
        # Двойная проверка: user_id из токена должен совпадать с переданным user_id
        if payload['user_id'] != data['user_id']:
            # Логирование предупреждения о несоответствии user_id
            logger.warning(f"Безопасность: Несоответствие user_id! Токен={payload['user_id']}, Запрос={data['user_id']}")
            return jsonify({
                "error": "Доступ запрещён",
                "details": "Токен не соответствует предоставленному user_id"
            }), 403
            
        # Получаем данные пользователя из сервиса
        user_data = UserDataService.get_user_data(payload['user_id'])
        
        if not user_data:
            # Логирование предупреждения о том, что пользователь не найден
            logger.warning(f"Пользователь не найден: {payload['user_id']}")
            return jsonify({"error": "Пользователь не найден"}), 404
            
        # Логирование успешного получения данных пользователя
        logger.info(f"Успешно получены данные для user_id={payload['user_id']}")
        return jsonify(user_data), 200
        
    except jwt.ExpiredSignatureError:
        # Логирование предупреждения о просроченном токене
        logger.warning("Проверка токена не удалась - токен просрочен")
        return jsonify({
            "error": "Токен просрочен",
            "should_refresh": True  # Флаг, указывающий, что токен можно обновить
        }), 401
        
    except jwt.InvalidTokenError as e:
        # Логирование предупреждения о недействительном токене
        logger.warning(f"Проверка токена не удалась - недействительный токен: {str(e)}")
        return jsonify({
            "error": "Недействительный токен",
            "should_refresh": False  # Флаг, указывающий, что токен нельзя обновить
        }), 401
        
    except Exception as e:
        # Логирование общей ошибки при обработке запроса
        logger.error(f"Ошибка при обработке запроса на получение данных пользователя: {str(e)}", exc_info=True)
        return jsonify({
            "error": "Внутренняя ошибка сервера"
        }), 500