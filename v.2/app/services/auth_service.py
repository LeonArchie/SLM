import bcrypt
import time
from typing import Dict, Optional
from services.logger_service import LoggerService

logger = LoggerService.get_logger('app.auth')

class AuthService:
    @staticmethod
    def hash_password(password: str) -> str:
        """Генерация bcrypt хеша пароля"""
        logger.debug("Генерация хеша пароля")
        return bcrypt.hashpw(password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')

    @staticmethod
    def verify_password(input_pwd: str, stored_hash: str) -> bool:
        """Проверка пароля через bcrypt"""
        logger.debug("Проверка пароля")
        try:
            return bcrypt.checkpw(input_pwd.encode('utf-8'), stored_hash.encode('utf-8'))
        except Exception as e:
            logger.error(f"Ошибка проверки пароля: {str(e)}", exc_info=True)
            return False

    @staticmethod
    def authenticate_user(login: str, password: str, db_service) -> Optional[Dict]:
        """Основной метод аутентификации"""
        logger.info(f"Начало аутентификации для '{login}'")
        start_time = time.time()

        try:
            user = db_service.get_user_by_login(login)
            if not user:
                logger.warning(f"Пользователь '{login}' не найден")
                return None

            if not AuthService.verify_password(password, user['password_hash']):
                logger.warning(f"Неверный пароль для '{login}'")
                time.sleep(3)  # Защита от брутфорса
                return None

            logger.info(
                f"Успешная аутентификация '{login}' "
                f"(за {time.time()-start_time:.2f} сек)"
            )
            return {
                'id': user['userid'],
                'login': user['userlogin'],
                'name': user['full_name']
            }

        except Exception as e:
            logger.critical(
                f"Критическая ошибка аутентификации для '{login}': {str(e)}",
                exc_info=True
            )
            raise