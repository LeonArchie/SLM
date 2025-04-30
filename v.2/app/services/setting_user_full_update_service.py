from services.token_service import TokenService
from services.connect_db_service import DatabaseService
from services.logger_service import LoggerService
import re
import jwt
from datetime import datetime

logger = LoggerService.get_logger('app.user_full_update.service')

class UserFullUpdateService:
    @staticmethod
    def validate_input(data: dict) -> dict:
        """Валидация всех входных данных"""
        errors = {}
        
        # Обязательные поля в user_data
        required_fields = {
            'full_name': (r'^[а-яА-ЯёЁ\s-]{1,70}$', "Только русские буквы и пробелы (макс. 70)"),
            'userlogin': (r'^[a-zA-Z0-9_\-@.]+$', "Только латиница, цифры и спецсимволы"),
            'user_off_email': (r'^[^\s@]+@[^\s@]+\.[^\s@]+$', "Некорректный формат email")
        }
        
        for field, (pattern, error_msg) in required_fields.items():
            if field not in data['user_data'] or not data['user_data'][field]:
                errors[field] = "Обязательное поле"
            elif not re.fullmatch(pattern, str(data['user_data'][field])):
                errors[field] = error_msg
        
        # Необязательные поля
        validation_rules = {
            'name': (r'^[а-яА-ЯёЁ]{0,20}$', "Только русские буквы (макс. 20)"),
            'family': (r'^[а-яА-ЯёЁ]{0,20}$', "Только русские буквы (макс. 20)"),
            'department': (r'^[а-яА-ЯёЁ\s-.,()]{0,100}$', "Только русские буквы и спецсимволы (макс. 100)"),
            'post': (r'^[а-яА-ЯёЁ\s-.,()]{0,100}$', "Только русские буквы и спецсимволы (макс. 100)"),
            'personal_mail': (r'^[^\s@]+@[^\s@]+\.[^\s@]+$', "Некорректный формат email"),
            'corp_phone': (r'^(\+7|8)\d{10}$', "Формат: +79991234567 или 89991234567"),
            'telephone': (r'^(\+7|8)\d{10}$', "Формат: +79991234567 или 89991234567"),
            'tg_id': (r'^\d{0,15}$', "Только цифры (макс. 15)"),
            'tg_username': (r'^[a-zA-Z0-9@_\-]{0,32}$', "Латиница, цифры, @, _, - (макс. 32)")
        }
        
        for field, (pattern, error_msg) in validation_rules.items():
            if field in data['user_data'] and data['user_data'][field] is not None and not re.fullmatch(pattern, str(data['user_data'][field])):
                errors[field] = error_msg
        
        # Булевы поля
        bool_fields = ['visible_personal_mail', 'visible_corp_phone', 'visible_telephone']
        for field in bool_fields:
            if field in data['user_data'] and not isinstance(data['user_data'][field], bool):
                errors[field] = "Должно быть true или false"
        
        return errors

    @staticmethod
    def verify_token_and_privileges(access_token: str, user_admin_id: str) -> dict:
        """Проверка валидности токена и полномочий администратора"""
        try:
            # Проверка токена
            payload = TokenService.verify_token(access_token)
            if payload['user_id'] != user_admin_id:
                logger.warning(f"User_id из токена ({payload['user_id']}) не соответствует user_admin_id ({user_admin_id})")
                return {"error": "Токен не соответствует пользователю", "status_code": 403}
            
            # Проверка полномочий
            PRIVILEGE_ID = "4e6c22aa-621a-4260-8e26-c2f4177362ba"
            with DatabaseService.get_connection() as conn:
                with conn.cursor() as cur:
                    cur.execute("""
                        SELECT 1 FROM privileges 
                        WHERE userid = %s AND id_privileges = %s
                    """, (user_admin_id, PRIVILEGE_ID))
                    if not cur.fetchone():
                        logger.warning(f"У пользователя {user_admin_id} нет необходимых полномочий")
                        return {"error": "Недостаточно полномочий", "status_code": 403}
            
            return {}
            
        except jwt.ExpiredSignatureError:
            logger.warning("Токен просрочен")
            return {"error": "Токен просрочен", "should_refresh": True, "status_code": 401}
        except jwt.InvalidTokenError as e:
            logger.warning(f"Недействительный токен: {str(e)}")
            return {"error": "Недействительный токен", "status_code": 401}
        except Exception as e:
            logger.error(f"Ошибка проверки токена или полномочий: {str(e)}", exc_info=True)
            return {"error": "Ошибка проверки токена или полномочий", "status_code": 500}

    @staticmethod
    def normalize_phone(phone: str) -> str:
        """Нормализация телефонных номеров"""
        if not phone:
            return phone
        phone = phone.replace(' ', '').replace('-', '').replace('(', '').replace(')', '')
        if phone.startswith('8'):
            phone = '+7' + phone[1:]
        return phone

    @staticmethod
    def prepare_update_data(user_data: dict, admin_id: str) -> dict:
        """Подготовка данных для обновления"""
        update_fields = {}
        
        # Основные поля
        fields_to_update = [
            'name', 'family', 'full_name', 'department', 'post',
            'user_off_email', 'personal_mail', 'corp_phone', 'telephone',
            'tg_id', 'tg_username', 'userlogin'
        ]
        
        for field in fields_to_update:
            if field in user_data and user_data[field] is not None:
                update_fields[field] = user_data[field]
        
        # Булевы поля
        bool_fields = {
            'visible_personal_mail': 'visible_personal_mail',
            'visible_corp_phone': 'visible_corp_phone',
            'visible_telephone': 'visible_telephone'
        }
        
        for field, db_field in bool_fields.items():
            if field in user_data:
                update_fields[db_field] = user_data[field]
        
        # Нормализация телефонов
        for phone_field in ['corp_phone', 'telephone']:
            if phone_field in update_fields and update_fields[phone_field]:
                update_fields[phone_field] = UserFullUpdateService.normalize_phone(update_fields[phone_field])
        
        # Добавляем информацию о том, кто и когда изменил данные
        update_fields['changing'] = admin_id
        update_fields['changing_timestamp'] = datetime.now()
        
        return update_fields

    @staticmethod
    def update_user_in_db(user_update_id: str, update_data: dict) -> bool:
        """Обновление данных пользователя в БД"""
        try:
            with DatabaseService.get_connection() as conn:
                with conn.cursor() as cur:
                    # Формируем SET часть запроса
                    set_parts = []
                    values = []
                    
                    for field, value in update_data.items():
                        if field == 'changing_timestamp':
                            set_parts.append(f"{field} = NOW()")
                        else:
                            set_parts.append(f"{field} = %s")
                            values.append(value)
                    
                    values.append(user_update_id)
                    
                    query = f"""
                        UPDATE users 
                        SET {', '.join(set_parts)}
                        WHERE userid = %s
                        RETURNING userid
                    """
                    
                    cur.execute(query, values)
                    if not cur.fetchone():
                        return False
                    conn.commit()
                    return True
        except Exception as e:
            logger.error(f"Ошибка базы данных: {str(e)}", exc_info=True)
            raise

    @staticmethod
    def process_full_update(data: dict) -> dict:
        """Основной метод обработки запроса на полное обновление"""
        # Проверка обязательных полей запроса
        if 'user_data' not in data:
            return {"error": "Отсутствует user_data в запросе", "status_code": 400}
        
        # Валидация данных
        validation_errors = UserFullUpdateService.validate_input(data)
        if validation_errors:
            return {
                "error": "Ошибка валидации",
                "details": validation_errors,
                "status_code": 400
            }

        # Проверка токена и полномочий
        token_verify = UserFullUpdateService.verify_token_and_privileges(
            data['access_token'], 
            data['user_admin_id']
        )
        if token_verify:
            return token_verify

        # Подготовка данных для обновления
        update_fields = UserFullUpdateService.prepare_update_data(
            data['user_data'],
            data['user_admin_id']
        )

        # Обновление в БД
        try:
            if not UserFullUpdateService.update_user_in_db(data['user_update_id'], update_fields):
                return {"error": "Пользователь не найден", "status_code": 404}
            
            logger.info(f"Данные пользователя {data['user_update_id']} успешно обновлены администратором {data['user_admin_id']}")
            return {"success": True}
            
        except Exception as e:
            logger.error(f"Обновление не удалось: {str(e)}", exc_info=True)
            return {"error": "Ошибка обновления в базе данных", "status_code": 500}