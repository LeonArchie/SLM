import re
import time
from typing import Dict, Optional
from services.logger_service import LoggerService
from services.token_service import TokenService
from services.guid_generate_service import GuidGenerateService
from services.auth_service import AuthService

logger = LoggerService.get_logger('app.user.create')

class UserCreateService:
    @staticmethod
    def validate_email(email: str) -> bool:
        """Validate email format"""
        if not email:  # Email can be empty
            return True
        pattern = r'^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$'
        return bool(re.match(pattern, email))

    @staticmethod
    def validate_full_name(full_name: str) -> bool:
        """Validate full name (only Russian letters and space, max 70 chars)"""
        if not full_name:
            return False
        if len(full_name) > 70:
            return False
        return bool(re.match(r'^[а-яА-ЯёЁ\s]+$', full_name))

    @staticmethod
    def validate_userlogin(userlogin: str) -> bool:
        """Validate userlogin (only English letters, dots, underscores, hyphens)"""
        if not userlogin:
            return False
        return bool(re.match(r'^[a-zA-Z0-9._-]+$', userlogin))

    @staticmethod
    def create_user(access_token: str, requesting_user_id: str, user_data: Dict) -> Dict:
        """Create a new user after validating all requirements"""
        logger.info(f"Starting user creation process by user {requesting_user_id}")

        # Verify the access token matches the requesting user
        try:
            payload = TokenService.verify_token(access_token)
            if payload['user_id'] != requesting_user_id:
                logger.warning(f"Token user_id mismatch: {payload['user_id']} != {requesting_user_id}")
                return {"error": "Unauthorized"}, 401
        except Exception as e:
            logger.error(f"Token verification failed: {str(e)}")
            return {"error": "Invalid token"}, 401

        # Validate input data
        if not UserCreateService.validate_userlogin(user_data.get('userlogin')):
            logger.warning(f"Invalid userlogin: {user_data.get('userlogin')}")
            return {"error": "Invalid userlogin format"}, 400

        if not UserCreateService.validate_full_name(user_data.get('full_name')):
            logger.warning(f"Invalid full name: {user_data.get('full_name')}")
            return {"error": "Invalid full name format"}, 400

        if not UserCreateService.validate_email(user_data.get('email')):
            logger.warning(f"Invalid email: {user_data.get('email')}")
            return {"error": "Invalid email format"}, 400

        if not user_data.get('password_hash'):
            logger.warning("Password hash is required")
            return {"error": "Password is required"}, 400

        try:
            # Generate user ID
            user_id = GuidGenerateService.generate_guid()
            
            # Hash the password
            password_hash = AuthService.hash_password(user_data['password_hash'])
            
            # Prepare user data for DB
            new_user = {
                'userid': user_id,
                'userlogin': user_data['userlogin'],
                'full_name': user_data['full_name'],
                'email': user_data.get('email', ''),
                'password_hash': password_hash,
                'active': True,
                'add_ldap': False,
                'regtimes': int(time.time())
            }

            return new_user

        except Exception as e:
            logger.error(f"User creation failed: {str(e)}", exc_info=True)
            return {"error": "Internal server error"}, 500