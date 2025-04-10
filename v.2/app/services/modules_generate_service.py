from services.db_service import DatabaseService
from services.logger_service import LoggerService
from services.token_service import TokenService
from typing import List, Dict, Set
import jwt

logger = LoggerService.get_logger('app.modules.generate')

def get_user_privileges(user_id: str) -> List[str]:
    """Получает список разрешенных GUID для пользователя"""
    logger.debug(f"Получение привилегий для пользователя: {user_id}")
    
    try:
        with DatabaseService.get_connection() as conn:
            with conn.cursor() as cur:
                cur.execute(
                    "SELECT id_privileges FROM privileges WHERE userid = %s",
                    (user_id,)
                )
                return [row[0] for row in cur.fetchall()]
    except Exception as e:
        logger.error(f"Ошибка получения привилегий: {str(e)}")
        raise

def get_user_menu(user_id: str, access_token: str, modules_data: dict):
    """Генерирует меню для пользователя с учетом прав и активности"""
    logger.debug(f"Генерация меню для пользователя: {user_id}")
    
    try:
        token_payload = TokenService.verify_token(access_token)
        if token_payload.get("user_id") != user_id:
            raise ValueError("Несовпадение user_id")
    except Exception as e:
        logger.error(f"Ошибка проверки токена: {str(e)}")
        raise

    allowed_guids = set(get_user_privileges(user_id))
    return filter_menu(modules_data["menu"], allowed_guids)

def filter_menu(menu: list, allowed_guids: Set[str]) -> list:
    """Фильтрует меню с учетом прав, активности и иерархии"""
    filtered_menu = []

    for item in menu:
        # Пропускаем неактивные пункты
        if not item.get("active", True):
            continue

        # Проверяем есть ли доступные дочерние элементы
        has_accessible_children = False
        filtered_dropdown = []

        if item.get("dropdown"):
            for child in item["dropdown"]:
                # Пропускаем неактивные дочерние элементы
                if not child.get("active", True):
                    continue
                
                # Проверяем права на дочерний элемент
                if child["guid"] in allowed_guids:
                    filtered_dropdown.append({
                        "title": child["title"],
                        "url": child["url"]
                    })
                    has_accessible_children = True

        # Если есть доступные дочерние элементы или есть права на родительский
        if has_accessible_children or item["guid"] in allowed_guids:
            menu_item = {
                "title": item["title"],
                "url": item["url"]
            }
            if filtered_dropdown:
                menu_item["dropdown"] = filtered_dropdown
            filtered_menu.append(menu_item)

    logger.debug(f"Отфильтровано {len(filtered_menu)} пунктов меню")
    return filtered_menu