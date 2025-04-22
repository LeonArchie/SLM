from services.connect_db_service import DatabaseService
from services.token_service import TokenService
from services.logger_service import logger
from services.read_config_service import get_config

def check_privilege(access_token: str, privilege_id: str, user_id: str) -> bool:
    """Проверяет наличие привилегии у пользователя с учетом активности FROD"""
    try:
        # Загрузка конфигурации приложения
        config = get_config()
        frod_active = config.get('frod', {}).get('active', True)  # Проверка, включен ли FROD
        
        # 1. Всегда проверяем токен на валидность и соответствие user_id
        payload = TokenService.verify_token(access_token)
        if payload['user_id'] != user_id:
            # Логирование предупреждения о несоответствии user_id из токена и запроса
            logger.warning(f"Несоответствие: user_id из токена {payload['user_id']} != запрошенный {user_id}")
            return False

        # 2. Если FROD выключен - автоматически предоставляем доступ
        if not frod_active:
            # Логирование информации о том, что FROD отключен
            logger.info(f"FROD отключен, доступ предоставлен для user_id={user_id}")
            return True

        # 3. Проверяем наличие привилегии в базе данных
        with DatabaseService.get_connection() as conn:
            with conn.cursor() as cur:
                cur.execute(
                    "SELECT 1 FROM privileges WHERE userid = %s AND id_privileges = %s LIMIT 1",
                    (user_id, privilege_id)
                )
                # Если запись найдена, возвращаем True, иначе False
                return bool(cur.fetchone())

    except Exception as e:
        # Логирование ошибки при проверке привилегий
        logger.error(f"Ошибка при проверке привилегий: {str(e)}")
        return False