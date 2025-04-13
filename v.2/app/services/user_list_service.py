from services.db_service import DatabaseService
from services.logger_service import LoggerService
from typing import List, Dict

logger = LoggerService.get_logger('app.user_list')

class UserListService:
    @staticmethod
    def get_user_list() -> List[Dict]:
        """Retrieve list of all users with specified fields from database"""
        logger.info("Fetching user list from database")
        
        try:
            with DatabaseService.get_connection() as conn:
                with conn.cursor() as cur:
                    cur.execute(
                        """
                        SELECT 
                            userid,
                            active,
                            add_ldap,
                            email,
                            full_name,
                            telephone,
                            userlogin
                        FROM users
                        ORDER BY userlogin
                        """
                    )
                    users = cur.fetchall()
                    
                    # Convert to list of dictionaries
                    user_list = []
                    for user in users:
                        user_list.append({
                            'userid': user[0],
                            'active': user[1],
                            'add_ldap': user[2],
                            'email': user[3],
                            'full_name': user[4],
                            'telephone': user[5],
                            'userlogin': user[6]
                        })
                    
                    logger.debug(f"Retrieved {len(user_list)} users")
                    return user_list
                    
        except Exception as e:
            logger.error(f"Failed to fetch user list: {str(e)}", exc_info=True)
            raise