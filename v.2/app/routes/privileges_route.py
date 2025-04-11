import jwt
from flask import Blueprint, request, jsonify
from services.token_service import TokenService
from services.privileges_service import PrivilegesService
from services.logger_service import LoggerService

logger = LoggerService.get_logger('app.privileges')
privileges_bp = Blueprint('privileges', __name__)

@privileges_bp.route('/privileges/user_view', methods=['POST'])
def get_user_privileges():
    """Получение привилегий пользователя"""
    logger.info("Received user privileges request")
    
    try:
        # Получаем данные из запроса
        data = request.get_json()
        if not data or 'access_token' not in data or 'user_id' not in data:
            logger.warning("Invalid request - missing required fields")
            return jsonify({"error": "access_token and user_id are required"}), 400

        # Проверяем токен
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

        # Получаем привилегии пользователя
        privileges = PrivilegesService.get_user_privileges(data['user_id'])
        
        logger.info(f"Successfully retrieved privileges for user {data['user_id']}")
        return jsonify({
            "privileges": privileges
        })
        
    except Exception as e:
        logger.error(f"Error getting user privileges: {str(e)}", exc_info=True)
        return jsonify({"error": "Internal server error"}), 500