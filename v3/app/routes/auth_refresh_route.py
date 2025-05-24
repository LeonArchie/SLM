# Импорт необходимых компонентов
from flask import Blueprint, request, jsonify, current_app  # Базовые компоненты Flask
from services.token_service import TokenService  # Сервис работы с токенами
from services.logger_service import LoggerService  # Сервис логирования
import jwt  # Для работы с JWT токенами

# Инициализация логгера для модуля обновления токенов
logger = LoggerService.get_logger('app.auth.refresh')

# Создание Blueprint для роутов обновления токенов
refresh_bp = Blueprint('refresh', __name__)

@refresh_bp.route('/auth/refresh', methods=['POST'])
def refresh_tokens():
    """
    Роут для обновления пары токенов (access + refresh)
    Требует валидный refresh token в теле запроса
    Возвращает новую пару токенов с информацией о времени жизни access token
    """
    logger.info("Получен запрос на обновление токенов")
    
    try:
        # Получаем и валидируем входные данные
        data = request.get_json()
        if not data or 'refresh_token' not in data:
            logger.warning("Не удалось обновить токены - refresh token не предоставлен")
            return jsonify({"error": "Требуется refresh token"}), 400

        # Верификация refresh токена
        try:
            payload = TokenService.verify_token(data['refresh_token'])
            # Проверяем тип токена (должен быть 'refresh')
            if payload['type'] != 'refresh':
                logger.warning("Не удалось обновить токены - передан не refresh token")
                return jsonify({"error": "Предоставлен не refresh token"}), 400
                
        except jwt.ExpiredSignatureError:  # Если токен просрочен
            logger.warning("Не удалось обновить токены - refresh token просрочен")
            return jsonify({
                "error": "Refresh token просрочен",
                "requires_login": True  # Флаг, требующий полной аутентификации
            }), 401

        # Генерация новой пары токенов
        new_tokens = TokenService.generate_tokens(payload['user_id'])
        
        logger.info(f"Токены успешно обновлены для user_id={payload['user_id']}")
        return jsonify({
            "access_token": new_tokens[0],  # Новый access token
            "refresh_token": new_tokens[1],  # Новый refresh token
            "expires_in": current_app.config['JWT_ACCESS_TOKEN_EXPIRES']  # Время жизни access token
        })
        
    except jwt.InvalidTokenError as e:  # Обработка невалидного токена
        logger.warning(f"Не удалось обновить токены - невалидный токен: {str(e)}")
        return jsonify({
            "error": "Невалидный refresh token",
            "requires_login": True  # Флаг, требующий полной аутентификации
        }), 401
        
    except Exception as e:  # Обработка всех остальных ошибок
        logger.error(f"Ошибка при обновлении токенов: {str(e)}", exc_info=True)
        return jsonify({
            "error": "Внутренняя ошибка сервера при обновлении токенов"
        }), 500