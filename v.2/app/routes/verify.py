from flask import Blueprint, request, jsonify, current_app
from services.token_service import TokenService
from services.logger_service import LoggerService
import jwt

logger = LoggerService.get_logger('app.auth.verify')
verify_bp = Blueprint('verify', __name__)

@verify_bp.route('/auth/verify', methods=['POST'])
def verify_token():
    """Проверка валидности JWT токена"""
    logger.info("Received token verification request")
    
    try:
        # Получаем токен из тела запроса
        data = request.get_json()
        if not data or 'token' not in data:
            logger.warning("Token verification failed - no token provided")
            return jsonify({"error": "Token is required"}), 400

        # Проверяем токен
        payload = TokenService.verify_token(data['token'])
        
        logger.info(f"Token verified successfully for user_id={payload['user_id']}")
        return jsonify({
            "valid": True,
            "user_id": payload['user_id'],
            "token_type": payload['type'],
            "expires_at": payload['exp']
        })
        
    except jwt.ExpiredSignatureError:
        logger.warning("Token verification failed - token expired")
        return jsonify({
            "valid": False,
            "error": "Token expired",
            "should_refresh": True
        }), 401
        
    except jwt.InvalidTokenError as e:
        logger.warning(f"Token verification failed - invalid token: {str(e)}")
        return jsonify({
            "valid": False,
            "error": "Invalid token",
            "should_refresh": False
        }), 401
        
    except Exception as e:
        logger.error(f"Token verification error: {str(e)}", exc_info=True)
        return jsonify({
            "error": "Internal server error during token verification"
        }), 500