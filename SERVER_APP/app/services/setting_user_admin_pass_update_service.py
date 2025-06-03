from typing import Dict
from services.logger_service import LoggerService
from services.token_service import TokenService
from services.connect_db_service import DatabaseService
from services.auth_login_service import AuthService
import time

# Инициализация логгера
logger = LoggerService.get_logger('app.user.admin.pass.update')

class UserAdminPassUpdateService:
    # Пароль по умолчанию (можно вынести в конфиг)
    DEFAULT_PASSWORD = "DefaultPassword123"  # В реальном проекте используйте более сложный пароль
    
    @staticmethod
    def admin_reset_password(
        access_token: str,
        admin_id: str,
        admin_pass: str,
        user_id: str
    ) -> Dict:
        """Сброс пароля пользователя администратором"""
        logger.info(f"Попытка сброса пароля для user_id={user_id} администратором admin_id={admin_id}")

        try:
            # 1. Проверка токена администратора
            try:
                payload = TokenService.verify_token(access_token)
                if payload['user_id'] != admin_id:
                    logger.warning(f"Несоответствие user_id в токене: {payload['user_id']} != {admin_id}")
                    return {
                        'success': False,
                        'error': 'Токен не соответствует admin_id',
                        'status_code': 401
                    }
            except Exception as e:
                logger.error(f"Ошибка проверки токена: {str(e)}")
                return {
                    'success': False,
                    'error': 'Недействительный токен',
                    'status_code': 401
                }

            # 2. Проверка пароля администратора
            with DatabaseService.get_connection() as conn:
                with conn.cursor() as cur:
                    cur.execute(
                        "SELECT password_hash FROM users WHERE userid = %s",
                        (admin_id,)
                    )
                    result = cur.fetchone()
                    if not result:
                        logger.warning(f"Администратор не найден: {admin_id}")
                        return {
                            'success': False,
                            'error': 'Администратор не найден',
                            'status_code': 404
                        }
                    
                    admin_hash = result[0]
                    if not AuthService.verify_password(admin_pass, admin_hash):
                        logger.warning("Неверный пароль администратора")
                        time.sleep(2)  # Задержка для защиты от брутфорса
                        return {
                            'success': False,
                            'error': 'Неверный пароль администратора',
                            'status_code': 403
                        }

            # 3. Проверка существования пользователя
            with DatabaseService.get_connection() as conn:
                with conn.cursor() as cur:
                    cur.execute(
                        "SELECT 1 FROM users WHERE userid = %s",
                        (user_id,)
                    )
                    if not cur.fetchone():
                        logger.warning(f"Пользователь не найден: {user_id}")
                        return {
                            'success': False,
                            'error': 'Пользователь не найден',
                            'status_code': 404
                        }

            # 4. Установка пароля по умолчанию
            new_password_hash = AuthService.hash_password(UserAdminPassUpdateService.DEFAULT_PASSWORD)
            
            with DatabaseService.get_connection() as conn:
                with conn.cursor() as cur:
                    cur.execute(
                        "UPDATE users SET password_hash = %s WHERE userid = %s",
                        (new_password_hash, user_id)
                    )
                    conn.commit()

            logger.info(f"Пароль пользователя {user_id} успешно сброшен администратором {admin_id}")
            return {
                'success': True,
                'message': 'Пароль успешно сброшен',
                'new_password': UserAdminPassUpdateService.DEFAULT_PASSWORD  # Только для отладки!
            }

        except Exception as e:
            logger.error(f"Ошибка сброса пароля: {str(e)}", exc_info=True)
            return {
                'success': False,
                'error': 'Внутренняя ошибка сервера',
                'status_code': 500
            }