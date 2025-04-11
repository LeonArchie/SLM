from flask import Blueprint, request, jsonify
from services.logger_service import LoggerService
from services.user_pass_update_service import UserPassUpdateService

logger = LoggerService.get_logger('app.user.pass.update.route')
user_pass_update_bp = Blueprint('user_pass_update', __name__)

@user_pass_update_bp.route('/user/pass/update', methods=['POST'])
def update_password():
    """Endpoint for updating user password"""
    logger.info("Received password update request")

    try:
        data = request.get_json()
        required_fields = ['access_token', 'user_id', 'old_pass', 'new_pass_1', 'new_pass_2']
        
        if not data or any(field not in data for field in required_fields):
            logger.warning("Invalid request - missing required fields")
            return jsonify({
                'error': 'All fields are required: access_token, user_id, old_pass, new_pass_1, new_pass_2'
            }), 400

        result = UserPassUpdateService.update_password(
            access_token=data['access_token'],
            user_id=data['user_id'],
            old_password=data['old_pass'],
            new_password_1=data['new_pass_1'],
            new_password_2=data['new_pass_2']
        )

        if not result['success']:
            return jsonify({'error': result['error']}), result.get('status_code', 400)

        return jsonify({'message': result['message']}), 200

    except Exception as e:
        logger.error(f"Password update route error: {str(e)}", exc_info=True)
        return jsonify({'error': 'Internal server error'}), 500