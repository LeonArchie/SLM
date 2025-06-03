from services.connect_db_service import DatabaseService
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
                            full_name, name, family, user_off_email, api_key, ldap_dn, 
                            add_ldap, active, api_key, department, personal_mail,
                            visible_corp_phone, corp_phone, post, visible_telephone,
                            visible_personal_mail
                        FROM users 
                        WHERE userid = %s
                        """,
                        (user_id,)
                    )
                    result = cur.fetchone()
                    
                    if result:
                        # Обработка NULL значений и формирование словаря с данными пользователя
                        user_data = {
                            'userid': result[0] or '',
                            'userlogin': result[1] or '',
                            'tg_username': result[2] or '',
                            'tg_id': str(result[3]) if result[3] is not None else '',
                            'telephone': result[4] or '',
                            'full_name': result[5] or '',
                            'name': result[6] or '',
                            'family': result[7] or '',
                            'user_off_email': result[8] or '',
                            'api_key': result[9] or result[13] or '',  # Обрабатываем два поля api_key
                            'ldap_dn': result[10] or '',
                            'add_ldap': bool(result[11]) if result[11] is not None else False,
                            'active': bool(result[12]) if result[12] is not None else False,
                            'department': result[14] or '',
                            'personal_mail': result[15] or '',
                            'visible_corp_phone': bool(result[16]) if result[16] is not None else False,
                            'corp_phone': result[17] or '',
                            'post': result[18] or '',
                            'visible_telephone': bool(result[19]) if result[19] is not None else False,
                            'visible_personal_mail': bool(result[20]) if result[20] is not None else False
                        }
                        return user_data
                    
                    # Если пользователь не найден, возвращаем None
                    return None
                    
        except Exception as e:
            # Логирование ошибки при получении данных пользователя
            logger.error(f"Не удалось получить данные пользователя: {str(e)}", exc_info=True)
            raise