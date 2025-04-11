from services.token_service import TokenService
from services.db_service import DatabaseService
from services.logger_service import LoggerService
import re
import jwt

logger = LoggerService.get_logger('app.user_update.service')

class UserUpdateService:
    @staticmethod
    def validate_input(data: dict) -> dict:
        """Валидация входных данных"""
        errors = {}
        
        # Email validation
        if not re.fullmatch(r'^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$', data['email']):
            errors['email'] = "Invalid email format"
        
        # Full name validation
        if not re.fullmatch(r'^[а-яА-ЯёЁ\s]{1,70}$', data['full_name']):
            errors['full_name'] = "Only Russian characters and spaces, max 70 symbols"
        
        # Optional fields validation
        if 'family' in data and not re.fullmatch(r'^[а-яА-ЯёЁ]{0,20}$', data['family']):
            errors['family'] = "Only Russian characters, max 20 symbols"
        
        if 'name' in data and not re.fullmatch(r'^[а-яА-ЯёЁ]{0,20}$', data['name']):
            errors['name'] = "Only Russian characters, max 20 symbols"
        
        if 'telephone' in data and not re.fullmatch(r'^\+7\d{10}$', data['telephone']):
            errors['telephone'] = "Must start with +7 and contain 11 digits"
        
        if 'tg_id' in data and not re.fullmatch(r'^\d{0,15}$', data['tg_id']):
            errors['tg_id'] = "Only digits, max 15 symbols"
        
        if 'tg_username' in data and not re.fullmatch(r'^[a-zA-Z0-9@_\-]{0,32}$', data['tg_username']):
            errors['tg_username'] = "Only Latin letters, @, _, -"
        
        return errors

    @staticmethod
    def verify_token(access_token: str, userid: str) -> dict:
        """Проверка валидности токена"""
        try:
            payload = TokenService.verify_token(access_token)
            if payload['user_id'] != userid:
                logger.warning("Token user_id doesn't match request userid")
                return {"error": "Token doesn't match user", "status_code": 403}
            return {}
        except jwt.ExpiredSignatureError:
            logger.warning("Token expired")
            return {"error": "Token expired", "should_refresh": True, "status_code": 401}
        except jwt.InvalidTokenError as e:
            logger.warning(f"Invalid token: {str(e)}")
            return {"error": "Invalid token", "status_code": 401}
        except Exception as e:
            logger.error(f"Token verification error: {str(e)}", exc_info=True)
            return {"error": "Token verification failed", "status_code": 500}

    @staticmethod
    def update_user_in_db(userid: str, update_data: dict) -> bool:
        """Обновление данных пользователя в БД"""
        try:
            with DatabaseService.get_connection() as conn:
                with conn.cursor() as cur:
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
            logger.error(f"Database error: {str(e)}", exc_info=True)
            raise

    @staticmethod
    def process_update(data: dict) -> dict:
        """Основной метод обработки запроса на обновление"""
        # Валидация данных
        validation_errors = UserUpdateService.validate_input(data)
        if validation_errors:
            return {
                "error": "Validation failed",
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
                return {"error": "User not found", "status_code": 404}
            
            logger.info(f"User {data['userid']} updated successfully")
            return {"success": True}
            
        except Exception as e:
            logger.error(f"Update failed: {str(e)}", exc_info=True)
            return {"error": "Database update failed", "status_code": 500}