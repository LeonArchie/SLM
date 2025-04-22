from services.token_service import TokenService
from services.connect_db_service import DatabaseService
from services.logger_service import LoggerService
import re
import jwt

# Инициализация логгера для модуля обновления данных пользователя
logger = LoggerService.get_logger('app.user_update.service')

class UserUpdateService:
    @staticmethod
    def validate_input(data: dict) -> dict:
        """Валидация входных данных"""
        errors = {}
        
        # Проверка email
        if not re.fullmatch(r'^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$', data['email']):
            errors['email'] = "Неверный формат email"
        
        # Проверка ФИО (только русские буквы и пробелы, максимум 70 символов)
        if not re.fullmatch(r'^[а-яА-ЯёЁ\s]{1,70}$', data['full_name']):
            errors['full_name'] = "Только русские символы и пробелы, максимум 70 символов"
        
        # Проверка необязательных полей
        
        # Проверка фамилии (только русские буквы, максимум 20 символов)
        if 'family' in data and not re.fullmatch(r'^[а-яА-ЯёЁ]{0,20}$', data['family']):
            errors['family'] = "Только русские символы, максимум 20 символов"
        
        # Проверка имени (только русские буквы, максимум 20 символов)
        if 'name' in data and not re.fullmatch(r'^[а-яА-ЯёЁ]{0,20}$', data['name']):
            errors['name'] = "Только русские символы, максимум 20 символов"
        
        # Проверка телефона (начинается с +7, содержит 11 цифр)
        if 'telephone' in data and not re.fullmatch(r'^\+7\d{10}$', data['telephone']):
            errors['telephone'] = "Должен начинаться с +7 и содержать 11 цифр"
        
        # Проверка Telegram ID (только цифры, максимум 15 символов)
        if 'tg_id' in data and not re.fullmatch(r'^\d{0,15}$', data['tg_id']):
            errors['tg_id'] = "Только цифры, максимум 15 символов"
        
        # Проверка Telegram username (только латинские буквы, @, _, -)
        if 'tg_username' in data and not re.fullmatch(r'^[a-zA-Z0-9@_\-]{0,32}$', data['tg_username']):
            errors['tg_username'] = "Только латинские буквы, @, _, -"
        
        return errors

    @staticmethod
    def verify_token(access_token: str, userid: str) -> dict:
        """Проверка валидности токена"""
        try:
            payload = TokenService.verify_token(access_token)
            if payload['user_id'] != userid:
                # Логирование предупреждения о несоответствии user_id из токена и запроса
                logger.warning("User_id из токена не соответствует user_id из запроса")
                return {"error": "Токен не соответствует пользователю", "status_code": 403}
            return {}
        except jwt.ExpiredSignatureError:
            # Логирование предупреждения о просроченном токене
            logger.warning("Токен просрочен")
            return {"error": "Токен просрочен", "should_refresh": True, "status_code": 401}
        except jwt.InvalidTokenError as e:
            # Логирование предупреждения о недействительном токене
            logger.warning(f"Недействительный токен: {str(e)}")
            return {"error": "Недействительный токен", "status_code": 401}
        except Exception as e:
            # Логирование ошибки при проверке токена
            logger.error(f"Ошибка проверки токена: {str(e)}", exc_info=True)
            return {"error": "Ошибка проверки токена", "status_code": 500}

    @staticmethod
    def update_user_in_db(userid: str, update_data: dict) -> bool:
        """Обновление данных пользователя в БД"""
        try:
            with DatabaseService.get_connection() as conn:
                with conn.cursor() as cur:
                    # Формирование части SQL-запроса для обновления полей
                    set_clause = ", ".join([f"{field} = %s" for field in update_data])
                    values = list(update_data.values())
                    values.append(userid)
                    
                    query = f"""
                        UPDATE users 
                        SET {set_clause}
                        WHERE userid = %s
                        RETURNING userid
                    """
                    
                    cur.execute(query, values)
                    if not cur.fetchone():
                        return False
                    conn.commit()
                    return True
        except Exception as e:
            # Логирование ошибки при работе с базой данных
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
        update_fields = {
            'email': data['email'],
            'full_name': data['full_name']
        }
        
        optional_fields = ['family', 'name', 'telephone', 'tg_id', 'tg_username']
        for field in optional_fields:
            if field in data:
                update_fields[field] = data[field]

        # Обновление в БД
        try:
            if not UserUpdateService.update_user_in_db(data['userid'], update_fields):
                return {"error": "Пользователь не найден", "status_code": 404}
            
            # Логирование успешного обновления данных пользователя
            logger.info(f"Данные пользователя {data['userid']} успешно обновлены")
            return {"success": True}
            
        except Exception as e:
            # Логирование ошибки при обновлении данных
            logger.error(f"Обновление не удалось: {str(e)}", exc_info=True)
            return {"error": "Ошибка обновления в базе данных", "status_code": 500}