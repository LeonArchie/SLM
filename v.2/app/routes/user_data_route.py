from flask import Blueprint, request, jsonify
from services.token_service import TokenService
from services.user_data_service import UserDataService
from services.logger_service import LoggerService
import jwt

logger = LoggerService.get_logger('app.user.data')
user_data_bp = Blueprint('user_data', __name__)

@user_data_bp.route('/user/data', methods=['POST'])
def get_user_data():
    """Получение данных пользователя по user_id с проверкой токена"""
    logger.info("Received user data request")
    
    try:
        # Получаем данные из запроса
        data = request.get_json()
        if not data or 'token' not in data or 'user_id' not in data:
            logger.warning("Invalid request - token and user_id are required")
            return jsonify({"error": "Token and user_id are required"}), 400

        # Проверяем токен
        payload = TokenService.verify_token(data['token'])
        
        # Проверяем соответствие user_id в токене и запросе
        if payload['user_id'] != data['user_id']:
            logger.warning(f"User ID mismatch: token={payload['user_id']}, request={data['user_id']}")
            return jsonify({"error": "User ID does not match token"}), 403
            
        # Получаем данные пользователя
        user_data = UserDataService.get_user_data(data['user_id'])
        
        if not user_data:
            logger.warning(f"User not found: {data['user_id']}")
            return jsonify({"error": "User not found"}), 404
            
        logger.info(f"Successfully retrieved data for user_id={data['user_id']}")
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