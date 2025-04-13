from flask import Blueprint, request, jsonify
from services.token_service import TokenService
from services.user_block_service import UserBlockService
from services.logger_service import LoggerService
import jwt

logger = LoggerService.get_logger('app.user_block')
user_block_bp = Blueprint('user_block', __name__)

@user_block_bp.route('/setting/user/block', methods=['POST'])
def block_user():
    """Block or unblock user accounts"""
    logger.info("Received user block/unblock request")
    
    try:
        # Get data from request
        data = request.get_json()
        if not data or 'access_token' not in data or 'user_id' not in data or 'block_user_id' not in data:
            logger.warning("Invalid request - missing required fields")
            return jsonify({"error": "access_token, user_id and block_user_id are required"}), 400

        # Verify token and check if it matches user_id
        try:
            payload = TokenService.verify_token(data['access_token'])
            if payload['user_id'] != data['user_id']:
                logger.warning(f"Token user_id mismatch: {payload['user_id']} != {data['user_id']}")
                return jsonify({"error": "Token does not match requested user"}), 403
        except jwt.ExpiredSignatureError:
            logger.warning("Token expired")
            return jsonify({"error": "Token expired", "should_refresh": True}), 401
        except jwt.InvalidTokenError:
            logger.warning("Invalid token")
            return jsonify({"error": "Invalid token"}), 401

        # Process block/unblock request
        result = UserBlockService.process_block_request(data['user_id'], data['block_user_id'])
        
        return jsonify({
            "success": True,
            "message": "User status updated successfully",
            "results": result
        })
        
    except Exception as e:
        logger.error(f"Error in block/unblock operation: {str(e)}", exc_info=True)
        return jsonify({"error": "Internal server error"}), 500