from flask import Blueprint, request, jsonify, current_app
from services.token_service import TokenService
from services.logger_service import LoggerService
import jwt

logger = LoggerService.get_logger('app.auth.refresh')
refresh_bp = Blueprint('refresh', __name__)

@refresh_bp.route('/auth/refresh', methods=['POST'])
def refresh_tokens():
    """Обновление пары токенов (access + refresh)"""
    logger.info("Received token refresh request")
    
    try:
        # Получаем refresh токен из тела запроса
        data = request.get_json()
        if not data or 'refresh_token' not in data:
            logger.warning("Token refresh failed - no refresh token provided")
            return jsonify({"error": "Refresh token is required"}), 400

        # Проверяем, что это действительно refresh токен
        try:
            payload = TokenService.verify_token(data['refresh_token'])
            if payload['type'] != 'refresh':
                logger.warning("Token refresh failed - not a refresh token")
                return jsonify({"error": "Provided token is not a refresh token"}), 400
        except jwt.ExpiredSignatureError:
            logger.warning("Token refresh failed - refresh token expired")
            return jsonify({
                "error": "Refresh token expired",
                "requires_login": True
            }), 401

        # Генерируем новую пару токенов
        new_tokens = TokenService.generate_tokens(payload['user_id'])
        
        logger.info(f"Tokens refreshed successfully for user_id={payload['user_id']}")
        return jsonify({
            "access_token": new_tokens[0],
            "refresh_token": new_tokens[1],
            "expires_in": current_app.config['JWT_ACCESS_TOKEN_EXPIRES']
        })
        
    except jwt.InvalidTokenError as e:
        logger.warning(f"Token refresh failed - invalid token: {str(e)}")
        return jsonify({
            "error": "Invalid refresh token",
            "requires_login": True
        }), 401
        
    except Exception as e:
        logger.error(f"Token refresh error: {str(e)}", exc_info=True)
        return jsonify({
            "error": "Internal server error during token refresh"
        }), 500