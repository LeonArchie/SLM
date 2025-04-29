import re
from datetime import datetime
from typing import Dict, Optional, Tuple
from services.connect_db_service import DatabaseService
from services.token_service import TokenService
from services.logger_service import logger

# Шаблоны валидации полей
VALIDATION_PATTERNS = {
    'name': (r'^[а-яА-ЯёЁ]{0,20}$', "Только русские буквы (макс. 20)"),
    'family': (r'^[а-яА-ЯёЁ]{0,20}$', "Только русские буквы (макс. 20)"),
    'department': (r'^[а-яА-ЯёЁ\s\-.,()]{0,100}$', "Только русские буквы и спецсимволы (макс. 100)"),
    'post': (r'^[а-яА-ЯёЁ\s\-.,()]{0,100}$', "Только русские буквы и спецсимволы (макс. 100)"),
    'user_off_email': (r'^[^\s@]+@[^\s@]+\.[^\s@]+$', "Некорректный формат email"),
    'personal_mail': (r'^[^\s@]+@[^\s@]+\.[^\s@]+$', "Некорректный формат email"),
    'corp_phone': (r'^(\+7|8)\d{10}$', "Формат: +79991234567 или 89991234567"),
    'telephone': (r'^(\+7|8)\d{10}$', "Формат: +79991234567 или 89991234567"),
    'tg_id': (r'^\d{0,15}$', "Только цифры (макс. 15)"),
    'tg_username': (r'^[a-zA-Z0-9@_\-]{0,32}$', "Латиница, цифры, @, _, - (макс. 32)")
}

def validate_user_data(user_data: Dict) -> Tuple[bool, Optional[str]]:
    """Валидация данных пользователя"""
    for field, (pattern, error_msg) in VALIDATION_PATTERNS.items():
        if field in user_data and user_data[field] is not None:
            value = str(user_data[field])
            if value and not re.fullmatch(pattern, value):
                return False, f"{field}: {error_msg}"
    return True, None

def update_user_data(access_token: str, admin_user_id: str, update_user_id: str, user_data: Dict) -> Dict:
    """
    Обновляет данные пользователя с проверкой привилегий администратора
    
    Args:
        access_token: JWT токен администратора
        admin_user_id: ID администратора, выполняющего обновление
        update_user_id: ID пользователя, данные которого обновляются
        user_data: Данные для обновления
    
    Returns:
        Словарь с результатом операции
    """
    try:
        # 1. Проверка валидности токена и соответствия user_id
        payload = TokenService.verify_token(access_token)
        if payload['user_id'] != admin_user_id:
            logger.warning(f"Несоответствие user_id в токене ({payload['user_id']}) и запросе ({admin_user_id})")
            return {"status": False, "error": "Несоответствие токена и пользователя"}
        
        # 2. Проверка привилегии администратора
        with DatabaseService.get_connection() as conn:
            with conn.cursor() as cur:
                # Проверяем наличие привилегии у администратора
                cur.execute(
                    "SELECT 1 FROM privileges WHERE userid = %s AND id_privileges = %s LIMIT 1",
                    (admin_user_id, '4e6c22aa-621a-4260-8e26-c2f4177362ba')
                )
                if not cur.fetchone():
                    logger.warning(f"У пользователя {admin_user_id} нет необходимых привилегий")
                    return {"status": False, "error": "Недостаточно прав"}
                
                # 3. Валидация данных
                is_valid, error_msg = validate_user_data(user_data)
                if not is_valid:
                    logger.warning(f"Невалидные данные: {error_msg}")
                    return {"status": False, "error": error_msg}
                
                # 4. Подготовка данных для обновления
                update_fields = []
                update_values = []
                
                for field, value in user_data.items():
                    if field in VALIDATION_PATTERNS:
                        update_fields.append(field)
                        update_values.append(value if value != "" else None)
                
                # Добавляем служебные поля
                update_fields.extend(['changing', 'changing_timestamp'])
                update_values.extend([admin_user_id, datetime.now()])
                
                # 5. Формирование и выполнение SQL-запроса
                set_clause = ", ".join([f"{field} = %s" for field in update_fields])
                sql = f"""
                    UPDATE users 
                    SET {set_clause}
                    WHERE userid = %s
                    RETURNING userid
                """
                
                cur.execute(sql, (*update_values, update_user_id))
                
                if not cur.fetchone():
                    logger.warning(f"Пользователь {update_user_id} не найден")
                    return {"status": False, "error": "Пользователь не найден"}
                
                conn.commit()
                logger.info(f"Данные пользователя {update_user_id} успешно обновлены администратором {admin_user_id}")
                return {"status": True}
                
    except Exception as e:
        logger.error(f"Ошибка при обновлении данных пользователя: {str(e)}", exc_info=True)
        return {"status": False, "error": "Внутренняя ошибка сервера"}