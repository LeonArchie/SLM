from typing import Dict, List
from services.token_service import TokenService
from services.connect_db_service import DatabaseService
from services.logger_service import LoggerService
import jwt

# Инициализация логгера для модуля адресной книги
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
            # Проверка валидности токена
            payload = TokenService.verify_token(access_token)
            
            # Проверка типа токена (должен быть access)
            if payload['type'] != 'access':
                logger.warning("Недопустимый тип токена - требуется access-токен")
                return {"error": "Требуется access-токен", "status_code": 401}

            # Проверка соответствия user_id из токена и из запроса
            if str(payload['user_id']) != str(requesting_user_id):
                logger.warning(f"Несоответствие user_id: токен={payload['user_id']}, запрос={requesting_user_id}")
                return {"error": "User ID не соответствует токену", "status_code": 403}

            # Получение списка активных контактов из базы данных
            with DatabaseService.get_connection() as conn:
                with conn.cursor() as cur:
                    cur.execute(
                        "SELECT userid, full_name, telephone, visible_telephone, "
                        "user_off_email, personal_mail, visible_personal_mail, "
                        "corp_phone, visible_corp_phone, department, post "
                        "FROM users WHERE active = true"
                    )
                    contacts = []
                    for row in cur.fetchall():
                        contact = {
                            "user_id": row[0],
                            "full_name": row[1],
                            "telephone": row[2] if row[3] else "Телефон скрыт",
                            "user_off_email": row[4],
                            "personal_mail": row[5] if row[6] else "E-mail скрыт",
                            "corp_phone": row[7] if row[8] else "Телефон скрыт",
                            "department": row[9],
                            "post": row[10]
                        }
                        contacts.append(contact)
                    
                    logger.info(f"Список активных контактов успешно получен для user_id={requesting_user_id}")
                    return {"contacts": contacts}

        except jwt.ExpiredSignatureError:
            logger.warning("Токен просрочен")
            return {"error": "Токен просрочен", "status_code": 401}
        except jwt.InvalidTokenError:
            logger.warning("Недействительный токен")
            return {"error": "Недействительный токен", "status_code": 401}
        except Exception as e:
            logger.error(f"Ошибка базы данных: {str(e)}", exc_info=True)
            return {"error": "Не удалось получить контакты", "status_code": 500}