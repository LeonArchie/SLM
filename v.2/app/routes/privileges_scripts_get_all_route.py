from flask import Blueprint, jsonify, request
from services.privileges_scripts_get_all_service import PrivilegesScriptsGetAllService
from services.logger_service import LoggerService
import jwt

logger = LoggerService.get_logger('app.privileges.scripts.route')

privileges_scripts_get_all_bp = Blueprint('privileges_scripts_get_all', __name__)

@privileges_scripts_get_all_bp.route('/privileges/scripts/get-all', methods=['POST'])
def get_all_scripts():
    """API для получения списка всех скриптов"""
    try:
        # Валидация входных данных
        data = request.get_json()
        if not data or 'access_token' not in data or 'user_id' not in data:
            return jsonify({
                "message": "Необходимы access_token и user_id",
                "privileges": [],
                "status": "error"
            }), 400

        access_token = data['access_token']
        user_id = data['user_id']

        # Проверка доступа
        if not PrivilegesScriptsGetAllService.verify_access(access_token, user_id):
            return jsonify({
                "message": "Неавторизованный доступ",
                "privileges": [],
                "status": "error"
            }), 401

        # Получение данных скриптов
        result = PrivilegesScriptsGetAllService.get_all_scripts_meta()
        return jsonify(result)

    except jwt.ExpiredSignatureError:
        return jsonify({
            "message": "Токен доступа истек",
            "privileges": [],
            "status": "error"
        }), 401
    except jwt.InvalidTokenError:
        return jsonify({
            "message": "Недействительный токен",
            "privileges": [],
            "status": "error"
        }), 401
    except Exception as e:
        logger.error(f"Ошибка API: {str(e)}")
        return jsonify({
            "message": "Внутренняя ошибка сервера",
            "privileges": [],
            "status": "error"
        }), 500