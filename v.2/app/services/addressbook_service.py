from typing import Dict, List
from services.token_service import TokenService
from services.db_service import DatabaseService
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
                # Логирование предупреждения о недопустимом типе токена
                logger.warning("Недопустимый тип токена - требуется access-токен")
                return {"error": "Требуется access-токен", "status_code": 401}

            # Проверка соответствия user_id из токена и из запроса
            if str(payload['user_id']) != str(requesting_user_id):
                # Логирование предупреждения о несоответствии user_id
                logger.warning(f"Несоответствие user_id: токен={payload['user_id']}, запрос={requesting_user_id}")
                return {"error": "User ID не соответствует токену", "status_code": 403}

            # Получение списка активных контактов из базы данных
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
                            "phone": row[2],  # Изменено с telephone на phone для единообразия
                            "email": row[3]
                        }
                        for row in cur.fetchall()
                    ]
                    # Логирование успешного получения списка контактов
                    logger.info(f"Список активных контактов успешно получен для user_id={requesting_user_id}")
                    return {"contacts": contacts}

        except jwt.ExpiredSignatureError:
            # Логирование предупреждения о просроченном токене
            logger.warning("Токен просрочен")
            return {"error": "Токен просрочен", "status_code": 401}
        except jwt.InvalidTokenError:
            # Логирование предупреждения о недействительном токене
            logger.warning("Недействительный токен")
            return {"error": "Недействительный токен", "status_code": 401}
        except Exception as e:
            # Логирование ошибки при работе с базой данных
            logger.error(f"Ошибка базы данных: {str(e)}", exc_info=True)
            return {"error": "Не удалось получить контакты", "status_code": 500}