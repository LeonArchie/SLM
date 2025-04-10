import jwt
from flask import Blueprint, request, jsonify
from services.modules_service import load_modules
from services.modules_generate_service import get_user_menu
from services.token_service import TokenService
from services.logger_service import LoggerService

# Создаем логгер для модуля
logger = LoggerService.get_logger('app.routes.modules')

modules_bp = Blueprint('modules', __name__)

@modules_bp.route("/user/modules", methods=["POST"])
def user_modules():
    """Роут для получения меню пользователя"""
    logger.info("Запрос на генерацию меню")
    
    try:
        data = request.json
        access_token = data.get("access_token")
        user_id = data.get("user_id")

        if not access_token or not user_id:
            return jsonify({"error": "Требуется access_token и user_id"}), 400

        # Проверка токена
        try:
            token_payload = TokenService.verify_token(access_token)
            if token_payload.get("user_id") != user_id:
                return jsonify({"error": "Неверный user_id"}), 403
        except Exception as e:
            return jsonify({"error": str(e)}), 401

        # Загрузка и фильтрация меню
        modules_data = load_modules()
        user_menu = get_user_menu(user_id, access_token, modules_data)

        return jsonify({"menu": user_menu})

    except Exception as e:
        logger.error(f"Ошибка: {str(e)}")
        return jsonify({"error": str(e)}), 400