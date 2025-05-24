# setting_user_update_service.py
from services.token_service import TokenService
from services.connect_db_service import DatabaseService
from services.logger_service import LoggerService
import re
import jwt
from datetime import datetime

logger = LoggerService.get_logger('app.user_update.service')

class UserUpdateService:
    @staticmethod
    def validate_input(data: dict) -> dict:
        """Валидация всех входных данных"""
        errors = {}
        
        # Обязательные поля
        if 'full_name' in data and not re.fullmatch(r'^[а-яА-ЯёЁ\s\-]{1,70}$', data['full_name']):
            errors['full_name'] = "Только русские буквы, пробелы и дефисы (макс. 70)"
        
        # Необязательные поля
        validation_rules = {
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
        
        for field, (pattern, error_msg) in validation_rules.items():
            if field in data and data[field] is not None and not re.fullmatch(pattern, str(data[field])):
                errors[field] = error_msg
        
        # Булевы поля
        bool_fields = ['visible_personal_mail', 'visible_corp_phone', 'visible_telephone']
        for field in bool_fields:
            if field in data and not isinstance(data[field], bool):
                errors[field] = "Должно быть true или false"
        
        return errors

    @staticmethod
    def verify_token(access_token: str, userid: str) -> dict:
        """Проверка валидности токена"""
        try:
            payload = TokenService.verify_token(access_token)
            if payload['user_id'] != userid:
                logger.warning("User_id из токена не соответствует user_id из запроса")
                return {"error": "Токен не соответствует пользователю", "status_code": 403}
            return {}
        except jwt.ExpiredSignatureError:
            logger.warning("Токен просрочен")
            return {"error": "Токен просрочен", "should_refresh": True, "status_code": 401}
        except jwt.InvalidTokenError as e:
            logger.warning(f"Недействительный токен: {str(e)}")
            return {"error": "Недействительный токен", "status_code": 401}
        except Exception as e:
            logger.error(f"Ошибка проверки токена: {str(e)}", exc_info=True)
            return {"error": "Ошибка проверки токена", "status_code": 500}

    @staticmethod
    def normalize_phone(phone: str) -> str:
        """Нормализация телефонных номеров"""
        if not phone:
            return phone
        return phone.replace(' ', '').replace('-', '').replace('(', '').replace(')', '')

    @staticmethod
    def prepare_update_data(data: dict) -> dict:
        """Подготовка данных для обновления"""
        update_fields = {}
        
        # Основные поля
        fields_to_update = [
            'name', 'family', 'full_name', 'department', 'post',
            'user_off_email', 'personal_mail', 'corp_phone', 'telephone',
            'tg_id', 'tg_username', 'api_key'
        ]
        
        for field in fields_to_update:
            if field in data and data[field] is not None:
                update_fields[field] = data[field]
        
        # Булевы поля
        bool_fields = {
            'visible_personal_mail': 'visible_personal_mail',
            'visible_corp_phone': 'visible_corp_phone',
            'visible_telephone': 'visible_telephone'
        }
        
        for field, db_field in bool_fields.items():
            if field in data:
                update_fields[db_field] = data[field]
        
        # Нормализация телефонов
        for phone_field in ['corp_phone', 'telephone']:
            if phone_field in update_fields and update_fields[phone_field]:
                update_fields[phone_field] = UserUpdateService.normalize_phone(update_fields[phone_field])
                if update_fields[phone_field].startswith('8'):
                    update_fields[phone_field] = '+7' + update_fields[phone_field][1:]
        
        # Добавляем информацию о том, кто и когда изменил данные
        update_fields['changing'] = data['userid']  # ID пользователя, который внес изменения
        update_fields['changing_timestamp'] = datetime.now()  # Текущая дата и время
        
        return update_fields

    @staticmethod
    def update_user_in_db(userid: str, update_data: dict) -> bool:
        """Обновление данных пользователя в БД"""
        try:
            with DatabaseService.get_connection() as conn:
                with conn.cursor() as cur:
                    # Формируем SET часть запроса с учетом типа данных
                    set_parts = []
                    values = []
                    
                    for field, value in update_data.items():
                        if field == 'changing_timestamp':
                            set_parts.append(f"{field} = NOW()")
                        else:
                            set_parts.append(f"{field} = %s")
                            values.append(value)
                    
                    values.append(userid)
                    
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
    def process_update(data: dict) -> dict:
        """Основной метод обработки запроса на обновление"""
        # Валидация данных
        validation_errors = UserUpdateService.validate_input(data)
        if validation_errors:
            return {
                "error": "Ошибка валидации",
                "details": validation_errors,
                "status_code": 400
            }

        # Проверка токена
        token_verify = UserUpdateService.verify_token(data['access_token'], data['userid'])
        if token_verify:
            return token_verify

        # Подготовка данных для обновления
        update_fields = UserUpdateService.prepare_update_data(data)

        # Обновление в БД
        try:
            if not UserUpdateService.update_user_in_db(data['userid'], update_fields):
                return {"error": "Пользователь не найден", "status_code": 404}
            
            logger.info(f"Данные пользователя {data['userid']} успешно обновлены")
            return {"success": True}
            
        except Exception as e:
            logger.error(f"Обновление не удалось: {str(e)}", exc_info=True)
            return {"error": "Ошибка обновления в базе данных", "status_code": 500}