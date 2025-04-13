import uuid
from services.logger_service import LoggerService

# Инициализация логгера для модуля генерации GUID
logger = LoggerService.get_logger('app.guid')

class GuidGenerateService:
    @staticmethod
    def generate_guid() -> str:
        """
        Генерация нового глобально-уникального идентификатора (GUID)
        
        Returns:
            str: Сгенерированный GUID в виде строки
        """
        # Генерация нового GUID с использованием библиотеки uuid
        new_guid = str(uuid.uuid4())
        
        # Логирование сгенерированного GUID для отслеживания
        logger.debug(f"Сгенерирован новый GUID: {new_guid}")
        
        return new_guid