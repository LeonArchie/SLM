from services.logger_service import LoggerService
from services.connect_db_service import DatabaseService
from services.token_service import TokenService
from typing import List, Dict

# Initialize logger for privilege service
logger = LoggerService.get_logger('app.privileges.get_all')

class PrivilegesGetAllService:
    @staticmethod
    def verify_token_and_user(access_token: str, user_id: str) -> bool:
        """Verify that the token is valid and matches the user_id"""
        try:
            payload = TokenService.verify_token(access_token)
            return payload['user_id'] == user_id
        except Exception as e:
            logger.error(f"Token verification failed: {str(e)}")
            return False

    @staticmethod
    def get_all_privileges() -> List[Dict]:
        """Get all privileges from the database"""
        logger.info("Fetching all privileges from database")
        
        try:
            with DatabaseService.get_connection() as conn:
                with conn.cursor() as cur:
                    cur.execute(
                        "SELECT id_privileges, name_privileges FROM name_privileges"
                    )
                    privileges = []
                    for row in cur.fetchall():
                        privileges.append({
                            'id_privileges': row[0],
                            'name_privileges': row[1]
                        })
                    return privileges
        except Exception as e:
            logger.error(f"Error fetching privileges: {str(e)}", exc_info=True)
            raise