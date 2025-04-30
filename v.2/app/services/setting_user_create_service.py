import re
import time
import datetime
from typing import Dict, Optional, Tuple
from services.logger_service import LoggerService
from services.token_service import TokenService
from services.guid_generate_service import GuidGenerateService
from services.auth_login_service import AuthService

# Инициализация логгера для модуля создания пользователя
logger = LoggerService.get_logger('app.user.create')

class UserCreateService:
    @staticmethod
    def validate_email(email: str) -> bool:
        """Проверка формата email"""
        if not email:  # Email может быть пустым
            return True
        pattern = r'^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$'
        return bool(re.match(pattern, email))

    @staticmethod
    def validate_full_name(full_name: str) -> bool:
        """Проверка ФИО (только русские буквы и пробелы, максимум 70 символов)"""
        if not full_name:
            return False
        if len(full_name) > 70:
            return False
        return bool(re.match(r'^[а-яА-ЯёЁ\s]+$', full_name))

    @staticmethod
    def validate_userlogin(userlogin: str) -> bool:
        """Проверка логина (только английские буквы, цифры, точки, подчеркивания, дефисы)"""
        if not userlogin:
            return False
        return bool(re.match(r'^[a-zA-Z0-9._-]+$', userlogin))

    @staticmethod
    def create_user(access_token: str, requesting_user_id: str, user_data: Dict) -> Dict:
        """Создание нового пользователя после проверки всех требований"""
        # Логирование начала процесса создания пользователя
        logger.info(f"Начало процесса создания пользователя от пользователя {requesting_user_id}")

        # Проверка токена на соответствие запрашивающему пользователю
        try:
            payload = TokenService.verify_token(access_token)
            if payload['user_id'] != requesting_user_id:
                logger.warning(f"Несоответствие user_id в токене: {payload['user_id']} != {requesting_user_id}")
                return {"error": "Неавторизован"}, 401
        except Exception as e:
            logger.error(f"Ошибка проверки токена: {str(e)}")
            return {"error": "Недействительный токен"}, 401

        # Проверка входных данных
        if not UserCreateService.validate_userlogin(user_data.get('userlogin')):
            logger.warning(f"Неверный логин: {user_data.get('userlogin')}")
            return {"error": "Неверный формат логина"}, 400

        if not UserCreateService.validate_full_name(user_data.get('full_name')):
            logger.warning(f"Неверное ФИО: {user_data.get('full_name')}")
            return {"error": "Неверный формат ФИО"}, 400

        if not UserCreateService.validate_email(user_data.get('user_off_email')):
            logger.warning(f"Неверный email: {user_data.get('user_off_email')}")
            return {"error": "Неверный формат email"}, 400

        if not user_data.get('password_hash'):
            logger.warning("Хеш пароля обязателен")
            return {"error": "Пароль обязателен"}, 400

        try:
            # Генерация уникального ID пользователя
            user_id = GuidGenerateService.generate_guid()
            
            # Хеширование пароля
            password_hash = AuthService.hash_password(user_data['password_hash'])
            
            # Подготовка данных пользователя
            new_user = {
                'userid': user_id,
                'userlogin': user_data['userlogin'],
                'full_name': user_data['full_name'],
                'user_off_email': user_data.get('user_off_email'),
                'password_hash': password_hash,
                'active': True,
                'add_ldap': False,
                'regtimes': datetime.datetime.now(),
                'reg_user_id': requesting_user_id
            }

            logger.info(f"Данные пользователя успешно подготовлены для user_id={user_id}")
            return new_user

        except Exception as e:
            logger.error(f"Ошибка создания пользователя: {str(e)}", exc_info=True)
            return {"error": "Внутренняя ошибка сервера"}, 500