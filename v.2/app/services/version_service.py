import json
import os
from typing import Dict, Any
from services.logger_service import LoggerService

logger = LoggerService.get_logger('app.version')

def read_version_config() -> Dict[str, Any]:
    """
    Чтение конфигурации версии из config.json
    Возвращает:
        Dict: Конфигурация версии или {'current_version': '0.0.0'} при ошибках
    """
    config_path = os.path.join(os.path.dirname(__file__), '..', 'config.json')
    default_version = {'current_version': '0.0.0'}
    
    logger.debug(f"Чтение конфигурации версии из {config_path}")
    
    try:
        # Проверка существования файла
        if not os.path.exists(config_path):
            logger.warning("Конфигурационный файл не найден, используется версия по умолчанию")
            return default_version
            
        with open(config_path, 'r') as config_file:
            try:
                config = json.load(config_file)
                version_config = config.get('version', default_version)
                
                # Валидация версии
                if 'current_version' not in version_config:
                    logger.error("Отсутствует current_version в конфигурации")
                    return default_version
                
                logger.info(f"Текущая версия приложения: {version_config['current_version']}")
                return version_config
                
            except json.JSONDecodeError as e:
                logger.error(f"Ошибка парсинга JSON: {str(e)}", exc_info=True)
                return default_version
                
    except Exception as e:
        logger.error(f"Ошибка чтения конфигурации версии: {str(e)}", exc_info=True)
        return default_version