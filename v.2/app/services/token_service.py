import jwt
import datetime
from flask import current_app
from services.logger_service import LoggerService

logger = LoggerService.get_logger('app.auth.token')

class TokenService:
    @staticmethod
    def generate_tokens(user_id: str) -> tuple:
        """Генерация JWT токенов"""
        logger.info(f"Генерация токенов для user_id={user_id}")
        
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

            logger.debug(f"Токены сгенерированы для user_id={user_id}")
            return access_token, refresh_token

        except Exception as e:
            logger.critical(
                f"Ошибка генерации токенов: {str(e)}",
                exc_info=True
            )
            raise