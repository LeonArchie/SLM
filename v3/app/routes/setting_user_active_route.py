from flask import Blueprint, request, jsonify
from services.setting_user_active_service import UserActiveService
from services.logger_service import LoggerService

logger = LoggerService.get_logger('app.user_active_route')

user_active_bp = Blueprint('user_active', __name__)

@user_active_bp.route('/setting/user/active/', methods=['POST'])
def get_user_active_status():
    try:
        data = request.get_json()
        user_id = data.get('user_id')
        
        # Используем сервис для получения статуса
        is_active = UserActiveService.get_user_active_status(user_id)
        
        return jsonify({"success": is_active}), 200
            
    except Exception as e:
        logger.error(f"Неожиданная ошибка: {str(e)}", exc_info=True)
        return jsonify({"success": False}), 200