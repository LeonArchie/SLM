import json
import os
from typing import Dict, Any
from services.logger_service import LoggerService

# Инициализация логгера для модуля чтения конфигурации версии
logger = LoggerService.get_logger('app.version')

def read_version_config() -> Dict[str, Any]:
    """
    Чтение конфигурации версии из config.json
    Возвращает:
        Dict: Конфигурация версии или {'current_version': '0.0.0'} при ошибках
    """
    # Определение пути к файлу конфигурации
    config_path = os.path.join(os.path.dirname(__file__), '..', 'config.json')
    default_version = {'current_version': '0.0.0'}
    
    # Логирование начала попытки чтения конфигурации версии
    logger.debug(f"Чтение конфигурации версии из {config_path}")
    
    try:
        # Проверка существования файла конфигурации
        if not os.path.exists(config_path):
            # Логирование предупреждения о том, что файл конфигурации отсутствует
            logger.warning("Конфигурационный файл не найден, используется версия по умолчанию")
            return default_version
            
        with open(config_path, 'r') as config_file:
            try:
                # Чтение и парсинг JSON-файла
                config = json.load(config_file)
                version_config = config.get('version', default_version)
                
                # Валидация наличия ключа current_version в конфигурации
                if 'current_version' not in version_config:
                    # Логирование ошибки об отсутствии current_version
                    logger.error("Отсутствует current_version в конфигурации")
                    return default_version
                
                # Логирование успешного чтения текущей версии приложения
                logger.info(f"Текущая версия приложения: {version_config['current_version']}")
                return version_config
                
            except json.JSONDecodeError as e:
                # Логирование ошибки парсинга JSON
                logger.error(f"Ошибка парсинга JSON: {str(e)}", exc_info=True)
                return default_version
                
    except Exception as e:
        # Логирование критической ошибки при чтении конфигурации версии
        logger.error(f"Ошибка чтения конфигурации версии: {str(e)}", exc_info=True)
        return default_version