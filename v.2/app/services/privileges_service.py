from services.db_service import DatabaseService
from services.logger_service import LoggerService
from typing import List, Dict

logger = LoggerService.get_logger('app.privileges.service')

class PrivilegesService:
    @staticmethod
    def get_user_privileges(user_id: str) -> List[Dict[str, str]]:
        """Получение списка привилегий пользователя"""
        logger.debug(f"Getting privileges for user {user_id}")
        
        try:
            with DatabaseService.get_connection() as conn:
                with conn.cursor() as cur:
                    # Получаем все id_privileges для пользователя из таблицы privileges
                    cur.execute(
                        "SELECT id_privileges FROM privileges WHERE userid = %s",
                        (user_id,)
                    )
                    privilege_ids = [row[0] for row in cur.fetchall()]
                    
                    if not privilege_ids:
                        return []
                    
                    # Получаем названия привилегий из таблицы name_privileges
                    cur.execute(
                        "SELECT id_privileges, name_privileges FROM name_privileges WHERE id_privileges IN %s",
                        (tuple(privilege_ids),)
                    )

                    privileges = [
                        {"id_privilege": row[0], "name_privilege": row[1]}
                        for row in cur.fetchall()
                    ]
                    
                    return privileges
                    
        except Exception as e:
            logger.error(f"Failed to get user privileges: {str(e)}", exc_info=True)
            raise