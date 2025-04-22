from services.connect_db_service import DatabaseService
from services.logger_service import LoggerService
from typing import List, Dict

# Инициализация логгера для модуля работы с привилегиями
logger = LoggerService.get_logger('app.privileges.service')

class PrivilegesService:
    @staticmethod
    def get_user_privileges(user_id: str) -> List[Dict[str, str]]:
        """Получение списка привилегий пользователя"""
        # Логирование начала процесса получения привилегий
        logger.debug(f"Получение привилегий для пользователя {user_id}")
        
        try:
            # Получение соединения с базой данных через контекстный менеджер
            with DatabaseService.get_connection() as conn:
                with conn.cursor() as cur:
                    # Выполнение запроса для получения всех id_privileges пользователя
                    cur.execute(
                        "SELECT id_privileges FROM privileges WHERE userid = %s",
                        (user_id,)
                    )
                    # Извлечение всех id_privileges из результатов запроса
                    privilege_ids = [row[0] for row in cur.fetchall()]
                    
                    # Если у пользователя нет привилегий, возвращаем пустой список
                    if not privilege_ids:
                        return []
                    
                    # Выполнение запроса для получения названий привилегий по их ID
                    cur.execute(
                        "SELECT id_privileges, name_privileges FROM name_privileges WHERE id_privileges IN %s",
                        (tuple(privilege_ids),)
                    )

                    # Формирование списка привилегий в виде словарей
                    privileges = [
                        {"id_privilege": row[0], "name_privilege": row[1]}
                        for row in cur.fetchall()
                    ]
                    
                    # Логирование успешного завершения операции
                    logger.debug(f"Успешно получено {len(privileges)} привилегий для пользователя {user_id}")
                    return privileges
                    
        except Exception as e:
            # Логирование ошибки при получении привилегий
            logger.error(f"Не удалось получить привилегии пользователя: {str(e)}", exc_info=True)
            raise