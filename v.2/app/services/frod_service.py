from services.db_service import DatabaseService
from services.token_service import TokenService
from services.logger_service import logger
from services.config_service import get_config

def check_privilege(access_token: str, privilege_id: str, user_id: str) -> bool:
    """Проверяет наличие привилегии у пользователя с учетом активности FROD"""
    try:
        config = get_config()
        frod_active = config.get('frod', {}).get('active', True)
        
        # 1. Всегда проверяем токен
        payload = TokenService.verify_token(access_token)
        if payload['user_id'] != user_id:
            logger.warning(f"Mismatch: token user {payload['user_id']} != requested {user_id}")
            return False

        # 2. Если FROD выключен - возвращаем True
        if not frod_active:
            logger.info(f"FROD disabled, access granted for user {user_id}")
            return True

        # 3. Проверяем привилегии в БД
        with DatabaseService.get_connection() as conn:
            with conn.cursor() as cur:
                cur.execute(
                    "SELECT 1 FROM privileges WHERE userid = %s AND id_privileges = %s LIMIT 1",
                    (user_id, privilege_id)
                )
                return bool(cur.fetchone())

    except Exception as e:
        logger.error(f"Privilege check failed: {str(e)}")
        return False