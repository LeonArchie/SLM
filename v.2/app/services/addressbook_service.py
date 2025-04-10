from typing import Dict, List
from services.token_service import TokenService
from services.db_service import DatabaseService
from services.logger_service import LoggerService
import jwt

logger = LoggerService.get_logger('app.addressbook')

class AddressBookService:
    @staticmethod
    def get_active_contacts(access_token: str, requesting_user_id: str) -> Dict:
        """
        Получает список активных контактов после проверки авторизации
        
        Args:
            access_token: JWT токен
            requesting_user_id: ID пользователя, делающего запрос
            
        Returns:
            Dict: {"contacts": List} или {"error": str, "status_code": int}
        """
        try:
            # Проверка токена
            payload = TokenService.verify_token(access_token)
            if payload['type'] != 'access':
                logger.warning("Invalid token type - access required")
                return {"error": "Access token required", "status_code": 401}

            if str(payload['user_id']) != str(requesting_user_id):
                logger.warning(f"User ID mismatch: token={payload['user_id']}, request={requesting_user_id}")
                return {"error": "User ID does not match token", "status_code": 403}

            # Получение контактов из БД
            with DatabaseService.get_connection() as conn:
                with conn.cursor() as cur:
                    cur.execute(
                        "SELECT userid, full_name, telephone, email "
                        "FROM users WHERE active = true"
                    )
                    contacts = [
                        {
                            "user_id": row[0],
                            "full_name": row[1],
                            "phone": row[2],  # Изменил telephone на phone для единообразия
                            "email": row[3]
                        }
                        for row in cur.fetchall()
                    ]
                    return {"contacts": contacts}

        except jwt.ExpiredSignatureError:
            logger.warning("Token expired")
            return {"error": "Token expired", "status_code": 401}
        except jwt.InvalidTokenError:
            logger.warning("Invalid token")
            return {"error": "Invalid token", "status_code": 401}
        except Exception as e:
            logger.error(f"Database error: {str(e)}", exc_info=True)
            return {"error": "Failed to fetch contacts", "status_code": 500}