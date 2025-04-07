import json
import os
from services.logger_service import LoggerService

# Инициализация логгера с указанием модуля
logger = LoggerService.get_logger('app.ldap')

def read_ldap_config():
    """
    Чтение LDAP конфигурации из config.json с обработкой ошибок
    Возвращает:
        dict: Конфигурация LDAP или {'active': False} при ошибках
    """
    config_path = os.path.join(os.path.dirname(__file__), '..', 'config.json')
    default_config = {'active': False}
    
    logger.debug(f"Попытка чтения конфигурации LDAP из {config_path}")
    
    try:
        # Проверка существования файла
        if not os.path.exists(config_path):
            logger.warning(f"Конфигурационный файл не найден: {config_path}")
            return default_config
            
        with open(config_path, 'r') as config_file:
            try:
                config = json.load(config_file)
                ldap_config = config.get('LDAP', default_config)
                
                # Валидация конфигурации
                if ldap_config.get('active'):
                    required_fields = ['domain_master', 'port', 'DN', 'username', 'password']
                    if not all(field in ldap_config for field in required_fields):
                        logger.error("Неполная LDAP конфигурация при active=true")
                        return default_config
                
                logger.info(f"Успешно загружена LDAP конфигурация. Active: {ldap_config.get('active')}")
                return ldap_config
                
            except json.JSONDecodeError as e:
                logger.error(f"Ошибка парсинга JSON: {str(e)}", exc_info=True)
                return default_config
                
    except Exception as e:
        logger.critical(f"Критическая ошибка чтения LDAP конфигурации: {str(e)}", exc_info=True)
        return default_config