import bcrypt
import time
from typing import Dict, Optional
from services.logger_service import LoggerService

# Инициализация логгера для модуля аутентификации
logger = LoggerService.get_logger('app.auth')

class AuthService:
    @staticmethod
    def hash_password(password: str) -> str:
        """Генерация bcrypt хеша пароля"""
        # Логирование начала процесса генерации хеша пароля
        logger.debug("Генерация хеша пароля")
        # Возвращаем хеш пароля, закодированный в UTF-8 и преобразованный в строку
        return bcrypt.hashpw(password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')

    @staticmethod
    def verify_password(input_pwd: str, stored_hash: str) -> bool:
        """Проверка пароля через bcrypt"""
        # Логирование начала процесса проверки пароля
        logger.debug("Проверка пароля")
        try:
            # Проверяем, совпадает ли введенный пароль с сохраненным хешем
            return bcrypt.checkpw(input_pwd.encode('utf-8'), stored_hash.encode('utf-8'))
        except Exception as e:
            # Логирование ошибки при проверке пароля
            logger.error(f"Ошибка проверки пароля: {str(e)}", exc_info=True)
            return False

    @staticmethod
    def authenticate_user(login: str, password: str, db_service) -> Optional[Dict]:
        """Основной метод аутентификации"""
        # Логирование начала процесса аутентификации пользователя
        logger.info(f"Начало аутентификации для '{login}'")
        start_time = time.time()  # Засекаем время начала аутентификации

        try:
            # Получение данных пользователя из базы данных по логину
            user = db_service.get_user_by_login(login)
            if not user:
                # Логирование предупреждения о том, что пользователь не найден
                logger.warning(f"Пользователь '{login}' не найден")
                return None

            # Проверка введенного пароля с сохраненным хешем
            if not AuthService.verify_password(password, user['password_hash']):
                # Логирование предупреждения о неверном пароле
                logger.warning(f"Неверный пароль для '{login}'")
                time.sleep(3)  # Защита от брутфорса (задержка времени ответа)
                return None

            # Логирование успешной аутентификации с указанием времени выполнения
            logger.info(
                f"Успешная аутентификация '{login}' "
                f"(за {time.time()-start_time:.2f} сек)"
            )
            # Возвращаем данные пользователя после успешной аутентификации
            return {
                'id': user['userid'],
                'login': user['userlogin'],
                'name': user['full_name']
            }

        except Exception as e:
            # Логирование критической ошибки при аутентификации
            logger.critical(
                f"Критическая ошибка аутентификации для '{login}': {str(e)}",
                exc_info=True
            )
            raise