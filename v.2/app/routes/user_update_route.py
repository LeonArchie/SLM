from flask import Blueprint, request, jsonify
from services.user_update_service import UserUpdateService
from services.logger_service import LoggerService

logger = LoggerService.get_logger('app.user_update')
user_update_bp = Blueprint('user_update', __name__)

@user_update_bp.route('/user/update', methods=['POST'])
def update_user():
    """Endpoint для обновления данных пользователя"""
    logger.info("Received user update request")
    
    try:
        data = request.get_json()
        if not data:
            logger.warning("No data provided in request")
            return jsonify({"error": "Request data is required"}), 400

        # Проверка обязательных полей
        required_fields = ['access_token', 'email', 'full_name', 'userid']
        missing_fields = [field for field in required_fields if field not in data]
        
        if missing_fields:
            logger.warning(f"Missing required fields: {missing_fields}")
            return jsonify({
                "error": "Missing required fields",
                "details": missing_fields
            }), 400

        # Вызов сервиса для обработки запроса
        result = UserUpdateService.process_update(data)
        
        if 'error' in result:
            return jsonify({
                "error": result['error'],
                "details": result.get('details')
            }), result.get('status_code', 400)
        
        return jsonify({"success": True, "message": ""}), 200

    except Exception as e:
        logger.error(f"Unexpected error: {str(e)}", exc_info=True)
        return jsonify({"error": "Internal server error"}), 500