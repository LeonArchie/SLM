from flask import Blueprint, request, jsonify
from services.logger_service import LoggerService
from services.auth_service import AuthService
from services.token_service import TokenService
from services.db_service import DatabaseService

logger = LoggerService.get_logger('app.auth.routes')
auth_bp = Blueprint('auth', __name__, url_prefix='/auth')

@auth_bp.route('/login', methods=['POST'])
def login():
    """Маршрут входа в систему"""
    logger.info(f"Запрос на /login от {request.remote_addr}")

    try:
        data = request.get_json()
        if not data or 'login' not in data or 'password' not in data:
            logger.warning("Невалидный запрос: отсутствует логин/пароль")
            return jsonify({"error": "Требуется логин и пароль"}), 400

        # Аутентификация через сервис
        user = AuthService.authenticate_user(
            data['login'],
            data['password'],
            DatabaseService
        )
        
        if not user:
            logger.warning(f"Неудачная аутентификация для '{data['login']}'")
            return jsonify({"error": "Неверные учетные данные"}), 401

        # Генерация токенов
        tokens = TokenService.generate_tokens(user['id'])
        logger.info(f"Успешный вход пользователя {user['id']}")

        return jsonify({
            "access_token": tokens[0],
            "refresh_token": tokens[1],
            "user_id": user['id'],
            "user_name": user['name']
        })

    except Exception as e:
        logger.error(f"Ошибка обработки /login: {str(e)}", exc_info=True)
        return jsonify({"error": "Внутренняя ошибка сервера"}), 500