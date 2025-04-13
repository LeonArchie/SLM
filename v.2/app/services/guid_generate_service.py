import uuid
from services.logger_service import LoggerService

logger = LoggerService.get_logger('app.guid')

class GuidGenerateService:
    @staticmethod
    def generate_guid() -> str:
        """Generate a new GUID"""
        new_guid = str(uuid.uuid4())
        logger.debug(f"Generated new GUID: {new_guid}")
        return new_guid