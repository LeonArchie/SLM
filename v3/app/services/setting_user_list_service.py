from services.connect_db_service import DatabaseService
from services.logger_service import LoggerService
from typing import List, Dict

# Инициализация логгера для модуля получения списка пользователей
logger = LoggerService.get_logger('app.user_list')

class UserListService:
    @staticmethod
    def get_user_list() -> List[Dict]:
        """Получение списка всех пользователей с указанными полями из базы данных"""
        # Логирование начала процесса получения списка пользователей
        logger.info("Получение списка пользователей из базы данных")
        
        try:
            # Получение соединения с базой данных через контекстный менеджер
            with DatabaseService.get_connection() as conn:
                with conn.cursor() as cur:
                    # Выполнение SQL-запроса для получения данных пользователей
                    cur.execute(
                        """
                        SELECT 
                            userid,
                            active,
                            add_ldap,
                            user_off_email,
                            full_name,
                            telephone,
                            userlogin
                        FROM users
                        ORDER BY userlogin
                        """
                    )
                    users = cur.fetchall()
                    
                    # Преобразование результатов в список словарей
                    user_list = []
                    for user in users:
                        user_list.append({
                            'userid': user[0],           # ID пользователя
                            'active': user[1],          # Активен ли пользователь
                            'add_ldap': user[2],        # Интеграция с LDAP
                            'user_off_email': user[3],          # Email пользователя
                            'full_name': user[4],      # Полное имя пользователя
                            'telephone': user[5],      # Телефон пользователя
                            'userlogin': user[6]       # Логин пользователя
                        })
                    
                    # Логирование успешного завершения операции с указанием количества пользователей
                    logger.debug(f"Получено {len(user_list)} пользователей")
                    return user_list
                    
        except Exception as e:
            # Логирование ошибки при получении списка пользователей
            logger.error(f"Не удалось получить список пользователей: {str(e)}", exc_info=True)
            raise