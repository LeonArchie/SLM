# Импорт необходимых компонентов
from flask import Blueprint, request, jsonify  # Базовые компоненты Flask
from services.token_service import TokenService  # Сервис работы с токенами
from services.setting_user_block_service import UserBlockService  # Сервис блокировки пользователей
from services.logger_service import LoggerService  # Сервис логирования
import jwt  # Для работы с JWT токенами

# Инициализация логгера для модуля блокировки пользователей
logger = LoggerService.get_logger('app.user_block')

# Создание Blueprint для роутов блокировки пользователей
user_block_bp = Blueprint('user_block', __name__)

@user_block_bp.route('/setting/user/block', methods=['POST'])
def block_user():
    """
    Роут для блокировки/разблокировки пользователей
    Требует:
    - access_token (токен доступа)
    - user_id (ID пользователя, выполняющего операцию)
    - block_user_id (ID пользователя для блокировки/разблокировки)
    Возвращает статус операции
    """
    logger.info("Получен запрос на блокировку/разблокировку пользователя")
    
    try:
        # Получаем и валидируем входные данные
        data = request.get_json()
        if not data or 'access_token' not in data or 'user_id' not in data or 'block_user_id' not in data:
            logger.warning("Невалидный запрос - отсутствуют обязательные поля")
            return jsonify({"error": "Требуются access_token, user_id и block_user_id"}), 400

        # Верификация токена и проверка прав доступа
        try:
            # Проверяем валидность токена
            payload = TokenService.verify_token(data['access_token'])
            
            # Проверяем соответствие user_id в токене и запросе
            if payload['user_id'] != data['user_id']:
                logger.warning(f"Несоответствие user_id в токене ({payload['user_id']}) и запросе ({data['user_id']})")
                return jsonify({"error": "Токен не соответствует указанному пользователю"}), 403
                
        except jwt.ExpiredSignatureError:  # Просроченный токен
            logger.warning("Токен просрочен")
            return jsonify({
                "error": "Токен просрочен", 
                "should_refresh": True  # Флаг необходимости обновить токен
            }), 401
        except jwt.InvalidTokenError:  # Невалидный токен
            logger.warning("Невалидный токен")
            return jsonify({"error": "Невалидный токен"}), 401

        # Обработка запроса на блокировку/разблокировку
        result = UserBlockService.process_block_request(
            data['user_id'],  # ID инициатора операции
            data['block_user_id']  # ID целевого пользователя
        )
        
        # Успешный ответ
        return jsonify({
            "success": True,
            "message": "Статус пользователя успешно обновлен",
            "results": result  # Детали операции
        })
        
    except Exception as e:  # Обработка непредвиденных ошибок
        logger.error(f"Ошибка при выполнении операции блокировки: {str(e)}", exc_info=True)
        return jsonify({
            "error": "Внутренняя ошибка сервера"
        }), 500