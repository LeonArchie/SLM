from flask import Blueprint, jsonify, request
from services.privileges_scripts_user_view_service import PrivilegesScriptsUserViewService
from services.logger_service import LoggerService
import jwt

logger = LoggerService.get_logger('app.privileges.scripts.user_view.route')

privileges_scripts_user_view_bp = Blueprint('privileges_scripts_user_view', __name__)

@privileges_scripts_user_view_bp.route('/privileges/scripts/user_view', methods=['POST'])
def user_scripts_view():
    """API для получения скриптов пользователя"""
    try:
        # Валидация входных данных
        data = request.get_json()
        if not data or 'access_token' not in data or 'user_id' not in data:
            return jsonify({
                "message": "Необходимы access_token и user_id",
                "scripts": [],
                "status": "error"
            }), 400

        # Получение данных скриптов пользователя
        result = PrivilegesScriptsUserViewService.get_user_scripts(
            data['access_token'],
            data['user_id']
        )

        # Возврат результата с соответствующим статусом
        if result['status'] == 'error':
            return jsonify(result), 401 if result['message'] == "Неавторизованный доступ" else 500
        return jsonify(result)

    except jwt.ExpiredSignatureError:
        return jsonify({
            "message": "Токен доступа истек",
            "scripts": [],
            "status": "error"
        }), 401
    except jwt.InvalidTokenError:
        return jsonify({
            "message": "Недействительный токен",
            "scripts": [],
            "status": "error"
        }), 401
    except Exception as e:
        logger.error(f"Ошибка API: {str(e)}")
        return jsonify({
            "message": "Внутренняя ошибка сервера",
            "scripts": [],
            "status": "error"
        }), 500