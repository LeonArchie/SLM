import os
import json
import jwt
from typing import Dict, List
from flask import current_app
from services.logger_service import LoggerService
from services.token_service import TokenService

logger = LoggerService.get_logger('app.privileges.scripts')

class PrivilegesScriptsGetAllService:
    @staticmethod
    def verify_access(access_token: str, user_id: str) -> bool:
        """Проверяет валидность токена и соответствие user_id"""
        try:
            payload = TokenService.verify_token(access_token)
            token_user_id = payload.get('user_id')
            if token_user_id != user_id:
                logger.warning(f"Несоответствие user_id: токен для {token_user_id}, запрос от {user_id}")
            return token_user_id == user_id
        except jwt.ExpiredSignatureError:
            logger.warning("Истек срок действия токена")
            return False
        except jwt.InvalidTokenError:
            logger.warning("Недействительный токен")
            return False
        except Exception as e:
            logger.error(f"Ошибка верификации токена: {str(e)}")
            return False

    @staticmethod
    def get_scripts_dir() -> str:
        """Возвращает абсолютный путь к директории скриптов"""
        return os.path.abspath(os.path.join(
            os.path.dirname(current_app.root_path),
            'app',
            'scripts',
            'template'
        ))

    @staticmethod
    def get_all_scripts_meta() -> Dict[str, List[Dict[str, str]]]:
        """Получает метаданные всех скриптов"""
        scripts_dir = PrivilegesScriptsGetAllService.get_scripts_dir()
        
        if not os.path.exists(scripts_dir):
            logger.error(f"Директория скриптов не найдена: {scripts_dir}")
            return {
                "message": "Директория скриптов не найдена",
                "privileges": [],
                "status": "error"
            }

        privileges_list = []
        
        try:
            for filename in os.listdir(scripts_dir):
                if filename.endswith('.json'):
                    file_path = os.path.join(scripts_dir, filename)
                    try:
                        with open(file_path, 'r', encoding='utf-8') as f:
                            data = json.load(f)
                            if 'meta' in data:
                                privileges_list.append({
                                    "id_privileges": data['meta'].get('guid_scripts', ''),
                                    "name_privileges": data['meta'].get('name_scripts', '')
                                })
                    except json.JSONDecodeError:
                        logger.warning(f"Невалидный JSON в файле: {filename}")
                    except Exception as e:
                        logger.error(f"Ошибка чтения файла {filename}: {str(e)}")

            return {
                "privileges": privileges_list,
                "status": "success"
            }

        except Exception as e:
            logger.critical(f"Критическая ошибка: {str(e)}")
            return {
                "message": "Внутренняя ошибка сервера",
                "privileges": [],
                "status": "error"
            }