from services.connect_db_service import DatabaseService
from services.privileges_user_view_service import PrivilegesService
from services.logger_service import LoggerService
from typing import List, Dict, Union

# Инициализация логгера для модуля блокировки/разблокировки пользователей
logger = LoggerService.get_logger('app.user_block.service')

class UserBlockService:
    REQUIRED_PRIVILEGE = "[Учетные записи] - Право блокировки учетной записи"

    @staticmethod
    def process_block_request(requesting_user_id: str, block_user_ids: Union[str, List[str]]) -> List[Dict]:
        """Обработка запроса на блокировку/разблокировку пользователей после проверки прав"""
        # Логирование начала обработки запроса
        logger.info(f"Обработка запроса на блокировку от пользователя {requesting_user_id} для пользователей {block_user_ids}")
        
        # Преобразование одиночного user_id в список для унифицированной обработки
        if isinstance(block_user_ids, str):
            block_user_ids = [block_user_ids]
        
        try:
            # Проверка наличия у запрашивающего пользователя необходимых прав
            privileges = PrivilegesService.get_user_privileges(requesting_user_id)
            has_privilege = any(priv['name_privilege'] == UserBlockService.REQUIRED_PRIVILEGE for priv in privileges)
            
            if not has_privilege:
                # Логирование предупреждения о недостаточных правах
                logger.warning(f"Пользователь {requesting_user_id} не имеет права на блокировку пользователей")
                raise PermissionError("Недостаточно прав для блокировки/разблокировки пользователей")
            
            # Обработка каждого пользователя из списка
            results = []
            with DatabaseService.get_connection() as conn:
                with conn.cursor() as cur:
                    for user_id in block_user_ids:
                        try:
                            # Проверка, не пытается ли пользователь заблокировать самого себя
                            if user_id == requesting_user_id:
                                results.append({
                                    "user_id": user_id,
                                    "success": False,
                                    "message": "Нельзя блокировать самого себя"
                                })
                                continue
                            
                            # Получение текущего статуса активности пользователя
                            cur.execute(
                                "SELECT active FROM users WHERE userid = %s",
                                (user_id,)
                            )
                            result = cur.fetchone()
                            
                            if not result:
                                # Если пользователь не найден, добавляем ошибку в результаты
                                results.append({
                                    "user_id": user_id,
                                    "success": False,
                                    "message": "Пользователь не найден"
                                })
                                continue
                            
                            current_status = result[0]
                            new_status = not current_status  # Инверсия текущего статуса
                            
                            # Обновление статуса пользователя
                            cur.execute(
                                "UPDATE users SET active = %s WHERE userid = %s",
                                (new_status, user_id)
                            )
                            conn.commit()
                            
                            # Добавление успешного результата в итоговый список
                            results.append({
                                "user_id": user_id,
                                "success": True,
                                "previous_status": current_status,
                                "new_status": new_status,
                                "message": "Статус успешно обновлен"
                            })
                            
                            # Логирование успешного изменения статуса
                            logger.info(f"Статус пользователя {user_id} изменен с {current_status} на {new_status}")
                            
                        except Exception as e:
                            # Откат транзакции при ошибке и логирование ошибки
                            conn.rollback()
                            logger.error(f"Не удалось обновить статус пользователя {user_id}: {str(e)}")
                            results.append({
                                "user_id": user_id,
                                "success": False,
                                "message": str(e)
                            })
            
            return results
            
        except PermissionError as e:
            # Перехват ошибки недостаточных прав
            raise
        except Exception as e:
            # Логирование общей ошибки при обработке запроса
            logger.error(f"Ошибка при обработке запроса на блокировку: {str(e)}", exc_info=True)
            raise