# services/auth_service.py
import bcrypt
from typing import Dict, Any
from services.db_service import DatabaseService
from services.logger_service import setup_logger
import time

logger = setup_logger()

def verify_credentials(username: str, password: str) -> Dict[str, Any]:
    """Проверяет учетные данные пользователя"""
    try:
        start_time = time.time()
        user = DatabaseService.get_user_by_login(username)
        
        if not user:
            logger.warning(f"Попытка входа несуществующего пользователя: {username}")
            return {'success': False, 'message': 'Неверный логин или пароль'}
        
        if not user['active']:
            logger.warning(f"Попытка входа заблокированного пользователя: {username}")
            return {'success': False, 'message': 'Пользователь заблокирован'}
        
        if bcrypt.checkpw(password.encode('utf-8'), user['password_hash'].encode('utf-8')):
            logger.info(f"Успешная авторизация пользователя: {username} за {time.time() - start_time:.2f} сек")
            return {
                'success': True,
                'user': {
                    'id': user['userid'],
                    'name': user['full_name'],
                    'login': user['userlogin']
                }
            }
        else:
            logger.warning(f"Неверный пароль для пользователя: {username}")
            time.sleep(3)  # Задержка для защиты от брутфорса
            return {'success': False, 'message': 'Неверный логин или пароль'}
            
    except Exception as e:
        logger.error(f"Ошибка при проверке учетных данных: {str(e)}")
        return {'success': False, 'message': 'Ошибка сервера'}