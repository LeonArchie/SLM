import os
import json
import jwt
from typing import Dict, List, Optional
from services.logger_service import LoggerService
from services.token_service import TokenService
from services.connect_db_service import DatabaseService

logger = LoggerService.get_logger('app.privileges.scripts.user_view')

class PrivilegesScriptsUserViewService:
    @staticmethod
    def verify_access(access_token: str, user_id: str) -> bool:
        """
        Проверяет валидность access_token.
        Не проверяет соответствие user_id токена и переданного user_id.
        
        Args:
            access_token: JWT токен для проверки
            user_id: ID пользователя (не используется в проверке)
            
        Returns:
            bool: True если токен валиден, False если не валиден
        """
        try:
            # Проверяем только валидность токена
            payload = TokenService.verify_token(access_token)
            
            # Успешная проверка токена (user_id игнорируется)
            logger.debug(f"Токен успешно верифицирован для пользователя {payload.get('user_id')}")
            return True
            
        except jwt.ExpiredSignatureError:
            logger.warning("Истек срок действия токена")
            return False
        except jwt.InvalidTokenError:
            logger.warning("Недействительный токен")
            return False
        except Exception as e:
            logger.error(f"Ошибка верификации токена: {str(e)}", exc_info=True)
            return False

    @staticmethod
    def get_scripts_dir() -> str:
        """Возвращает абсолютный путь к директории скриптов"""
        from flask import current_app
        return os.path.abspath(os.path.join(
            os.path.dirname(current_app.root_path),
            'app',
            'scripts',
            'template'
        ))

    @staticmethod
    def get_user_scripts_ids(user_id: str) -> List[str]:
        """Получает список ID скриптов пользователя из БД"""
        try:
            with DatabaseService.get_connection() as conn:
                with conn.cursor() as cur:
                    cur.execute(
                        "SELECT id_scripts FROM privileges_script WHERE userid = %s",
                        (user_id,)
                    )
                    return [row[0] for row in cur.fetchall()]
        except Exception as e:
            logger.error(f"Ошибка получения скриптов пользователя из БД: {str(e)}")
            raise

    @staticmethod
    def get_scripts_meta(script_ids: List[str]) -> List[Dict]:
        """Получает метаданные скриптов по их ID"""
        scripts_dir = PrivilegesScriptsUserViewService.get_scripts_dir()
        scripts_meta = []

        if not os.path.exists(scripts_dir):
            logger.error(f"Директория скриптов не найдена: {scripts_dir}")
            return scripts_meta

        for script_id in script_ids:
            file_path = os.path.join(scripts_dir, f"{script_id}.json")
            if not os.path.exists(file_path):
                logger.warning(f"Файл скрипта не найден: {script_id}.json")
                continue

            try:
                with open(file_path, 'r', encoding='utf-8') as f:
                    data = json.load(f)
                    if 'meta' in data:
                        scripts_meta.append(data['meta'])
            except json.JSONDecodeError:
                logger.warning(f"Невалидный JSON в файле: {script_id}.json")
            except Exception as e:
                logger.error(f"Ошибка чтения файла {script_id}.json: {str(e)}")

        return scripts_meta

    @staticmethod
    def get_user_scripts(access_token: str, user_id: str) -> Dict[str, any]:
        """Основной метод для получения скриптов пользователя"""
        if not PrivilegesScriptsUserViewService.verify_access(access_token, user_id):
            return {
                "message": "Неавторизованный доступ",
                "scripts": [],
                "status": "error"
            }

        try:
            script_ids = PrivilegesScriptsUserViewService.get_user_scripts_ids(user_id)
            scripts_meta = PrivilegesScriptsUserViewService.get_scripts_meta(script_ids)

            return {
                "scripts": scripts_meta,
                "status": "success"
            }
        except Exception as e:
            logger.error(f"Ошибка получения скриптов пользователя: {str(e)}")
            return {
                "message": "Внутренняя ошибка сервера",
                "scripts": [],
                "status": "error"
            }