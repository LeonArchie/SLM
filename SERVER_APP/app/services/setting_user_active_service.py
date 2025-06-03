from services.logger_service import LoggerService
from services.connect_db_service import DatabaseService

logger = LoggerService.get_logger('app.user_active_service')

class UserActiveService:
    @staticmethod
    def get_user_active_status(user_id: str) -> bool:
        """
        Проверяет статус active пользователя
        Возвращает:
        - True если пользователь существует и active=True
        - False во всех остальных случаях
        """
        if not user_id:
            logger.warning("Пустой user_id при проверке статуса active")
            return False

        logger.info(f"Проверка статуса active для user_id: {user_id}")

        try:
            with DatabaseService.get_connection() as conn:
                with conn.cursor() as cur:
                    cur.execute(
                        "SELECT active FROM users WHERE userid = %s",
                        (user_id,)
                    )
                    result = cur.fetchone()
                    
                    if not result:
                        logger.warning(f"Пользователь с user_id={user_id} не найден")
                        return False
                    
                    return bool(result[0])
                    
        except Exception as db_error:
            logger.error(f"Ошибка БД при проверке статуса active: {str(db_error)}")
            return False