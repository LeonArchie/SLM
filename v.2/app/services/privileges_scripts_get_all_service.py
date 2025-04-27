import os
import json
from typing import Dict, List, Optional
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
            return payload.get('user_id') == user_id
        except Exception as e:
            logger.error(f"Ошибка верификации токена: {str(e)}")
            return False

    @staticmethod
    def get_all_scripts_meta(user_id: str) -> Dict[str, List[Dict[str, str]]]:
        """Получает метаданные всех скриптов с проверкой доступа"""
        try:
            base_dir = os.path.abspath(os.path.dirname(current_app.root_path))
            scripts_dir = os.path.join(base_dir, 'scripts', 'template')
            
            if not os.path.exists(scripts_dir):
                logger.error(f"Директория не найдена: {scripts_dir}")
                return {"privileges": [], "status": "error", "message": "Директория скриптов не найдена"}

            privileges_list = []
            
            for filename in os.listdir(scripts_dir):
                if filename.endswith('.json'):
                    file_path = os.path.join(scripts_dir, filename)
                    try:
                        with open(file_path, 'r', encoding='utf-8') as f:
                            data = json.load(f)
                            if 'meta' in data:
                                meta = data['meta']
                                privileges_list.append({
                                    "id_privileges": meta.get('guid_scripts', ''),
                                    "name_privileges": meta.get('name_scripts', '')
                                })
                    except Exception as e:
                        logger.error(f"Ошибка обработки файла {filename}: {str(e)}")

            logger.info(f"Пользователь {user_id} получил {len(privileges_list)} скриптов")
            return {
                "privileges": privileges_list,
                "status": "success"
            }

        except Exception as e:
            logger.critical(f"Критическая ошибка: {str(e)}")
            return {
                "privileges": [],
                "status": "error",
                "message": "Внутренняя ошибка сервера"
            }