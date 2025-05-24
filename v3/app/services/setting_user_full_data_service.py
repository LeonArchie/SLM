from services.connect_db_service import DatabaseService
from services.token_service import TokenService
from services.logger_service import logger
from typing import Dict, Optional

def get_user_full_data(access_token: str, admin_user_id: str, check_user_id: str) -> Dict:
    """
    Получает полные данные пользователя с проверкой привилегий администратора
    
    Args:
        access_token: JWT токен администратора
        admin_user_id: ID администратора, запрашивающего данные
        check_user_id: ID пользователя, данные которого запрашиваются
    
    Returns:
        Словарь с данными пользователя или сообщением об ошибке
    """
    try:
        # 1. Проверка валидности токена и соответствия user_id
        payload = TokenService.verify_token(access_token)
        if payload['user_id'] != admin_user_id:
            logger.warning(f"Несоответствие user_id в токене ({payload['user_id']}) и запросе ({admin_user_id})")
            return {"error": "Несоответствие токена и пользователя", "status": False}
        
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
                    return {"error": "Недостаточно прав", "status": False}
                
                # 3. Получаем полные данные пользователя
                cur.execute(
                    """
                    SELECT 
                        userid, active, add_ldap, api_key, changing, changing_timestamp,
                        corp_phone, department, family, full_name, ldap_dn, name,
                        personal_mail, post, reg_user_id, regtimes, telephone,
                        tg_id, tg_username, user_off_email, userlogin,
                        visible_corp_phone, visible_telephone, visible_personal_mail
                    FROM users 
                    WHERE userid = %s
                    """,
                    (check_user_id,)
                )
                
                user_data = cur.fetchone()
                if not user_data:
                    logger.warning(f"Пользователь {check_user_id} не найден")
                    return {"error": "Пользователь не найден", "status": False}
                
                # Формируем словарь с данными пользователя
                columns = [
                    'userid', 'active', 'add_ldap', 'api_key', 'changing', 'changing_timestamp',
                    'corp_phone', 'department', 'family', 'full_name', 'ldap_dn', 'name',
                    'personal_mail', 'post', 'reg_user_id', 'regtimes', 'telephone',
                    'tg_id', 'tg_username', 'user_off_email', 'userlogin',
                    'visible_corp_phone', 'visible_telephone', 'visible_personal_mail'
                ]
                
                result = {
                    "status": True,
                    "user_data": dict(zip(columns, user_data))
                }
                
                logger.info(f"Успешно получены данные пользователя {check_user_id}")
                return result
                
    except Exception as e:
        logger.error(f"Ошибка при получении данных пользователя: {str(e)}", exc_info=True)
        return {"error": "Внутренняя ошибка сервера", "status": False}