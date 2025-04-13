from services.db_service import DatabaseService
from services.logger_service import LoggerService
from typing import Dict, Optional

# Инициализация логгера для модуля получения данных пользователя
logger = LoggerService.get_logger('app.user.data')

class UserDataService:
    @staticmethod
    def get_user_data(user_id: str) -> Optional[Dict]:
        """Получение данных пользователя по ID с обработкой NULL значений"""
        # Логирование начала процесса получения данных пользователя
        logger.debug(f"Получение данных пользователя для user_id={user_id}")
        
        try:
            # Получение соединения с базой данных через контекстный менеджер
            with DatabaseService.get_connection() as conn:
                with conn.cursor() as cur:
                    # Выполнение SQL-запроса для получения данных пользователя
                    cur.execute(
                        """
                        SELECT 
                            userid, userlogin, tg_username, tg_id, telephone,
                            regtimes, full_name, name, family, email,
                            api_key, ldap_dn, add_ldap, active
                        FROM users 
                        WHERE userid = %s
                        """,
                        (user_id,)
                    )
                    result = cur.fetchone()
                    
                    if result:
                        # Обработка NULL значений и формирование словаря с данными пользователя
                        return {
                            'userid': result[0] if result[0] is not None else '',
                            'userlogin': result[1] if result[1] is not None else '',
                            'tg_username': result[2] if result[2] is not None else '',
                            'tg_id': result[3] if result[3] is not None else '',
                            'telephone': result[4] if result[4] is not None else '',
                            'regtimes': result[5].isoformat() if result[5] is not None else '',  # Преобразование даты в строку
                            'full_name': result[6] if result[6] is not None else '',
                            'name': result[7] if result[7] is not None else '',
                            'family': result[8] if result[8] is not None else '',
                            'email': result[9] if result[9] is not None else '',
                            'api_key': result[10] if result[10] is not None else '',
                            'ldap_dn': result[11] if result[11] is not None else '',
                            'add_ldap': bool(result[12]) if result[12] is not None else False,  # Преобразование в булево значение
                            'active': bool(result[13]) if result[13] is not None else False    # Преобразование в булево значение
                        }
                    # Если пользователь не найден, возвращаем None
                    return None
                    
        except Exception as e:
            # Логирование ошибки при получении данных пользователя
            logger.error(f"Не удалось получить данные пользователя: {str(e)}", exc_info=True)
            raise