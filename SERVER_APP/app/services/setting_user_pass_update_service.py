from typing import Dict, Optional
from services.logger_service import LoggerService
from services.auth_login_service import AuthService
from services.token_service import TokenService
from services.connect_db_service import DatabaseService
import time

# Инициализация логгера для модуля обновления пароля пользователя
logger = LoggerService.get_logger('app.user.pass.update')

class UserPassUpdateService:
    @staticmethod
    def update_password(
        access_token: str,
        user_id: str,
        old_password: str,
        new_password_1: str,
        new_password_2: str
    ) -> Dict:
        """Обновление пароля пользователя после проверки всех требований"""
        # Логирование начала процесса обновления пароля
        logger.info(f"Начало процесса обновления пароля для user_id={user_id}")
        start_time = time.time()

        try:
            # Проверка токена на валидность и соответствие user_id
            try:
                token_payload = TokenService.verify_token(access_token)
                if token_payload['user_id'] != user_id:
                    # Логирование предупреждения о несоответствии user_id из токена и запроса
                    logger.warning(f"Несоответствие user_id в токене: {token_payload['user_id']} != {user_id}")
                    return {
                        'success': False,
                        'error': 'Токен не соответствует user_id',
                        'status_code': 401
                    }
            except Exception as e:
                # Логирование ошибки проверки токена
                logger.warning(f"Ошибка проверки токена: {str(e)}")
                return {
                    'success': False,
                    'error': 'Недействительный или просроченный токен',
                    'status_code': 401
                }

            # Получение хеша текущего пароля пользователя из базы данных
            with DatabaseService.get_connection() as conn:
                with conn.cursor() as cur:
                    cur.execute(
                        "SELECT password_hash FROM users WHERE userid = %s",
                        (user_id,)
                    )
                    result = cur.fetchone()
                    if not result:
                        # Логирование предупреждения о том, что пользователь не найден
                        logger.warning(f"Пользователь не найден: {user_id}")
                        return {
                            'success': False,
                            'error': 'Пользователь не найден',
                            'status_code': 404
                        }

                    stored_hash = result[0]

            # Проверка старого пароля
            if not AuthService.verify_password(old_password, stored_hash):
                # Логирование предупреждения о неверном старом пароле
                logger.warning(f"Проверка старого пароля не удалась для user_id={user_id}")
                time.sleep(2)  # Задержка для предотвращения атак перебором
                return {
                    'success': False,
                    'error': 'Старый пароль неверен',
                    'status_code': 401
                }

            # Проверка совпадения новых паролей
            if new_password_1 != new_password_2:
                # Логирование предупреждения о несовпадении новых паролей
                logger.warning("Новые пароли не совпадают")
                return {
                    'success': False,
                    'error': 'Новые пароли не совпадают',
                    'status_code': 400
                }

            # Проверка минимальной длины нового пароля
            if len(new_password_1) < 7:
                # Логирование предупреждения о слишком коротком новом пароле
                logger.warning("Новый пароль слишком короткий")
                return {
                    'success': False,
                    'error': 'Новый пароль должен содержать минимум 7 символов',
                    'status_code': 400
                }

            # Проверка, что новый пароль отличается от старого
            if AuthService.verify_password(new_password_1, stored_hash):
                # Логирование предупреждения о совпадении нового и старого паролей
                logger.warning("Новый пароль не должен совпадать со старым")
                return {
                    'success': False,
                    'error': 'Новый пароль должен отличаться от старого',
                    'status_code': 400
                }

            # Хеширование и сохранение нового пароля
            new_password_hash = AuthService.hash_password(new_password_1)

            with DatabaseService.get_connection() as conn:
                with conn.cursor() as cur:
                    cur.execute(
                        "UPDATE users SET password_hash = %s WHERE userid = %s",
                        (new_password_hash, user_id)
                    )
                    conn.commit()

            # Логирование успешного завершения операции с указанием времени выполнения
            logger.info(
                f"Пароль успешно обновлен для user_id={user_id} "
                f"(заняло {time.time()-start_time:.2f} сек)"
            )
            return {
                'success': True,
                'message': 'Пароль успешно обновлен'
            }

        except Exception as e:
            # Логирование общей ошибки при обновлении пароля
            logger.error(f"Ошибка обновления пароля: {str(e)}", exc_info=True)
            return {
                'success': False,
                'error': 'Внутренняя ошибка сервера',
                'status_code': 500
            }