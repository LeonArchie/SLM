# Импорт необходимых библиотек
import jwt  # Для работы с JWT-токенами
from flask import Blueprint, request, jsonify  # Компоненты Flask для API
from services.setting_user_modules_read_service import load_modules  # Сервис загрузки модулей
from services.setting_user_modules_generate_service import get_user_menu  # Сервис генерации меню
from services.token_service import TokenService  # Сервис работы с токенами
from services.logger_service import LoggerService  # Сервис логирования

# Инициализация логгера для этого модуля
logger = LoggerService.get_logger('app.routes.modules')

# Создание Blueprint для группировки роутов модулей
modules_bp = Blueprint('modules', __name__)

@modules_bp.route("/setting/user/modules", methods=["POST"])
def user_modules():
    """
    Роут для получения персонализированного меню пользователя
    Возвращает JSON с доступными для пользователя модулями
    """
    logger.info("Получен запрос на генерацию пользовательского меню")
    
    try:
        # Получаем данные из тела запроса
        data = request.json
        access_token = data.get("access_token")  # Токен доступа
        user_id = data.get("user_id")  # Идентификатор пользователя

        # Валидация обязательных полей
        if not access_token or not user_id:
            logger.warning("Отсутствуют обязательные поля (access_token или user_id)")
            return jsonify({"error": "Требуется access_token и user_id"}), 400

        # Проверка валидности токена
        try:
            token_payload = TokenService.verify_token(access_token)
            # Проверка соответствия user_id в токене и запросе
            if token_payload.get("user_id") != user_id:
                logger.warning(f"Несоответствие user_id в токене и запросе для пользователя {user_id}")
                return jsonify({"error": "Неверный user_id"}), 403
        except Exception as e:
            logger.error(f"Ошибка верификации токена: {str(e)}")
            return jsonify({"error": str(e)}), 401

        # Получение данных всех модулей
        modules_data = load_modules()
        
        # Генерация персонализированного меню для пользователя
        user_menu = get_user_menu(user_id, access_token, modules_data)

        logger.info(f"Успешно сгенерировано меню для пользователя {user_id}")
        return jsonify({"menu": user_menu})

    except Exception as e:
        # Обработка всех неожиданных ошибок
        logger.error(f"Критическая ошибка при генерации меню: {str(e)}")
        return jsonify({"error": str(e)}), 400