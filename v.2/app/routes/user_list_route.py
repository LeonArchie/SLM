from flask import Blueprint, request, jsonify
from services.user_list_service import UserListService
from services.logger_service import LoggerService
from services.token_service import TokenService

logger = LoggerService.get_logger('app.user_list')
user_list_bp = Blueprint('user_list', __name__)

@user_list_bp.route('/setting/user/list', methods=['POST'])
def get_user_list():
    """Endpoint to get user list with detailed information"""
    logger.info("Received user list request")
    
    try:
        # Get data from request
        data = request.get_json()
        if not data or 'user_id' not in data or 'access_token' not in data:
            logger.warning("Invalid request - missing required fields")
            return jsonify({"error": "user_id and access_token are required"}), 400

        user_id = data['user_id']
        access_token = data['access_token']

        # Verify token and user_id match
        try:
            payload = TokenService.verify_token(access_token)
            if payload['user_id'] != user_id:
                logger.warning(f"Token user_id mismatch: {payload['user_id']} != {user_id}")
                return jsonify({"error": "Invalid token for this user"}), 403
        except Exception as e:
            logger.warning(f"Token verification failed: {str(e)}")
            return jsonify({"error": "Token verification failed"}), 401

        # Get user list
        users = UserListService.get_user_list()
        
        logger.info(f"Successfully retrieved {len(users)} users")
        return jsonify({
            "status": "success",
            "users": users
        })

    except Exception as e:
        logger.error(f"Error in user list endpoint: {str(e)}", exc_info=True)
        return jsonify({
            "error": "Internal server error while processing user list"
        }), 500