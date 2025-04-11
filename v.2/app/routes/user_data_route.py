from flask import Blueprint, request, jsonify
from services.token_service import TokenService
from services.user_data_service import UserDataService
from services.logger_service import LoggerService
import jwt

logger = LoggerService.get_logger('app.user.data')
user_data_bp = Blueprint('user_data', __name__)

@user_data_bp.route('/user/data', methods=['POST'])
def get_user_data():
    """Получение данных пользователя с проверкой токена и user_id"""
    logger.info("Received user data request with token and user_id validation")
    
    try:
        # Получаем данные из запроса
        data = request.get_json()
        if not data or 'access_token' not in data or 'user_id' not in data:
            logger.warning("Invalid request - both token and user_id are required")
            return jsonify({"error": "Token and user_id are required"}), 400

        # Проверяем токен
        payload = TokenService.verify_token(data['access_token'])
        
        # Двойная проверка: user_id из токена должен совпадать с переданным user_id
        if payload['user_id'] != data['user_id']:
            logger.warning(f"Security alert: User ID mismatch! Token={payload['user_id']}, Request={data['user_id']}")
            return jsonify({
                "error": "Access denied",
                "details": "Token does not match provided user_id"
            }), 403
            
        # Получаем данные пользователя
        user_data = UserDataService.get_user_data(payload['user_id'])
        
        if not user_data:
            logger.warning(f"User not found: {payload['user_id']}")
            return jsonify({"error": "User not found"}), 404
            
        logger.info(f"Successfully retrieved data for user_id={payload['user_id']}")
        return jsonify(user_data), 200
        
    except jwt.ExpiredSignatureError:
        logger.warning("Token verification failed - token expired")
        return jsonify({
            "error": "Token expired",
            "should_refresh": True
        }), 401
        
    except jwt.InvalidTokenError as e:
        logger.warning(f"Token verification failed - invalid token: {str(e)}")
        return jsonify({
            "error": "Invalid token",
            "should_refresh": False
        }), 401
        
    except Exception as e:
        logger.error(f"User data request error: {str(e)}", exc_info=True)
        return jsonify({
            "error": "Internal server error"
        }), 500