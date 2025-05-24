import jwt
import datetime
from flask import current_app
from services.logger_service import LoggerService

# Инициализация логгера для модуля работы с токенами
logger = LoggerService.get_logger('app.auth.token')

class TokenService:
    @staticmethod
    def generate_tokens(user_id: str) -> tuple:
        """Генерация JWT токенов"""
        # Логирование начала процесса генерации токенов
        logger.info(f"Генерация токенов для user_id={user_id}")
        
        try:
            # Формирование payload для access-токена
            access_payload = {
                'user_id': user_id,  # ID пользователя
                'exp': datetime.datetime.utcnow() + datetime.timedelta(
                    seconds=current_app.config['JWT_ACCESS_TOKEN_EXPIRES']),  # Время истечения
                'type': 'access'  # Тип токена
            }
            # Формирование payload для refresh-токена
            refresh_payload = {
                'user_id': user_id,  # ID пользователя
                'exp': datetime.datetime.utcnow() + datetime.timedelta(
                    seconds=current_app.config['JWT_REFRESH_TOKEN_EXPIRES']),  # Время истечения
                'type': 'refresh'  # Тип токена
            }

            # Кодирование токенов с использованием секретного ключа
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

            # Логирование успешной генерации токенов
            logger.debug("Токены успешно сгенерированы")
            return access_token, refresh_token

        except Exception as e:
            # Логирование критической ошибки при генерации токенов
            logger.critical(f"Ошибка генерации токенов: {str(e)}", exc_info=True)
            raise

    @staticmethod
    def verify_token(token: str) -> dict:
        """Проверка JWT токена"""
        # Логирование начала процесса проверки токена
        logger.info("Проверка токена")
        try:
            # Декодирование и проверка токена
            payload = jwt.decode(
                token,
                current_app.config['JWT_SECRET_KEY'],
                algorithms=['HS256']
            )
            # Логирование успешной проверки токена
            logger.debug("Токен успешно проверен")
            return payload
        except jwt.ExpiredSignatureError:
            # Логирование предупреждения о просроченном токене
            logger.warning("Токен просрочен")
            raise
        except jwt.InvalidTokenError as e:
            # Логирование предупреждения о недействительном токене
            logger.warning(f"Недействительный токен: {str(e)}")
            raise
        except Exception as e:
            # Логирование ошибки при проверке токена
            logger.error(f"Ошибка проверки токена: {str(e)}", exc_info=True)
            raise

    @staticmethod
    def rotate_refresh_token(refresh_token: str) -> tuple:
        """Обновление refresh токена"""
        # Логирование начала процесса обновления токена
        logger.info("Обновление refresh токена")
        try:
            # Проверка refresh-токена
            payload = TokenService.verify_token(refresh_token)
            if payload['type'] != 'refresh':
                # Логирование ошибки, если передан не refresh-токен
                logger.error("Передан не refresh токен")
                raise ValueError("Не refresh токен")
            
            # Генерация новых токенов на основе данных из refresh-токена
            logger.debug("Refresh токен успешно обновлен")
            return TokenService.generate_tokens(payload['user_id'])
        except Exception as e:
            # Логирование ошибки при обновлении токена
            logger.error(f"Ошибка обновления токена: {str(e)}", exc_info=True)
            raise