import jwt
import datetime
from flask import current_app
from services.logger_service import LoggerService

logger = LoggerService.get_logger('app.auth.token')

class TokenService:
    @staticmethod
    def generate_tokens(user_id: str) -> tuple:
        """Генерация JWT токенов"""
        logger.info(f"Generating tokens for user_id={user_id}")
        
        try:
            access_payload = {
                'user_id': user_id,
                'exp': datetime.datetime.utcnow() + datetime.timedelta(
                    seconds=current_app.config['JWT_ACCESS_TOKEN_EXPIRES']),
                'type': 'access'
            }
            refresh_payload = {
                'user_id': user_id,
                'exp': datetime.datetime.utcnow() + datetime.timedelta(
                    seconds=current_app.config['JWT_REFRESH_TOKEN_EXPIRES']),
                'type': 'refresh'
            }

            access_token = jwt.encode(
                access_payload,
                current_app.config['JWT_SECRET_KEY'],
                algorithm='HS256'
            )
            refresh_token = jwt.encode(
                refresh_payload,
                current_app.config['JWT_SECRET_KEY'],
                algorithm='HS256'
            )

            return access_token, refresh_token

        except Exception as e:
            logger.critical(f"Token generation error: {str(e)}", exc_info=True)
            raise

    @staticmethod
    def verify_token(token: str) -> dict:
        """Проверка JWT токена"""
        logger.info("Verifying token")
        try:
            payload = jwt.decode(
                token,
                current_app.config['JWT_SECRET_KEY'],
                algorithms=['HS256']
            )
            return payload
        except jwt.ExpiredSignatureError:
            logger.warning("Token expired")
            raise
        except jwt.InvalidTokenError as e:
            logger.warning(f"Invalid token: {str(e)}")
            raise
        except Exception as e:
            logger.error(f"Token verification error: {str(e)}", exc_info=True)
            raise

    @staticmethod
    def rotate_refresh_token(refresh_token: str) -> tuple:
        """Обновление refresh токена"""
        logger.info("Rotating refresh token")
        try:
            payload = TokenService.verify_token(refresh_token)
            if payload['type'] != 'refresh':
                raise ValueError("Not a refresh token")
            return TokenService.generate_tokens(payload['user_id'])
        except Exception as e:
            logger.error(f"Token rotation error: {str(e)}", exc_info=True)
            raise