from services.db_service import DatabaseService
from services.logger_service import LoggerService
from typing import Dict, Optional

logger = LoggerService.get_logger('app.user.data')

class UserDataService:
    @staticmethod
    def get_user_data(user_id: str) -> Optional[Dict]:
        """Получение данных пользователя по ID"""
        logger.debug(f"Fetching user data for user_id={user_id}")
        
        try:
            with DatabaseService.get_connection() as conn:
                with conn.cursor() as cur:
                    cur.execute(
                        """
                        SELECT 
                            userid, userlogin, tg_username, tg_id, telephone,
                            regtimes, full_name, name, family, email,
                            api_key, dn, add_ldap, active
                        FROM users 
                        WHERE userid = %s
                        """,
                        (user_id,)
                    )
                    result = cur.fetchone()
                    
                    if result:
                        return {
                            'userid': result[0],
                            'userlogin': result[1],
                            'tg_username': result[2],
                            'tg_id': result[3],
                            'telephone': result[4],
                            'regtimes': result[5],
                            'full_name': result[6],
                            'name': result[7],
                            'family': result[8],
                            'email': result[9],
                            'api_key': result[10],
                            'dn': result[11],
                            'add_ldap': result[12],
                            'active': bool(result[13])
                        }
                    return None
                    
        except Exception as e:
            logger.error(f"Failed to fetch user data: {str(e)}", exc_info=True)
            raise