from flask import Blueprint, jsonify, request
from services.privileges_scripts_get_all_service import PrivilegesScriptsGetAllService
from services.logger_service import LoggerService

logger = LoggerService.get_logger('app.privileges.scripts.route')

privileges_scripts_get_all_bp = Blueprint('privileges_scripts_get_all', __name__)

@privileges_scripts_get_all_bp.route('/privileges/scripts/get-all', methods=['POST'])
def get_all_scripts():
    """API для получения списка скриптов с проверкой доступа"""
    try:
        data = request.get_json()
        if not data or 'access_token' not in data or 'user_id' not in data:
            return jsonify({
                "privileges": [],
                "status": "error",
                "message": "Необходимы access_token и user_id"
            }), 400

        access_token = data['access_token']
        user_id = data['user_id']

        logger.info(f"Запрос на получение скриптов от пользователя {user_id}")

        # Проверка доступа
        if not PrivilegesScriptsGetAllService.verify_access(access_token, user_id):
            return jsonify({
                "privileges": [],
                "status": "error",
                "message": "Неверный токен доступа"
            }), 403

        # Получение данных скриптов
        result = PrivilegesScriptsGetAllService.get_all_scripts_meta(user_id)
        return jsonify(result)

    except Exception as e:
        logger.error(f"Ошибка API: {str(e)}", exc_info=True)
        return jsonify({
            "privileges": [],
            "status": "error",
            "message": "Внутренняя ошибка сервера"
        }), 500