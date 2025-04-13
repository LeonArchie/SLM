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
    # Определение пути к файлу конфигурации
    config_path = os.path.join(os.path.dirname(__file__), '..', 'config.json')
    default_config = {'active': False}  # Конфигурация по умолчанию (LDAP отключен)
    
    # Логирование начала попытки чтения конфигурации
    logger.debug(f"Попытка чтения конфигурации LDAP из {config_path}")
    
    try:
        # Проверка существования файла конфигурации
        if not os.path.exists(config_path):
            # Логирование предупреждения о том, что файл конфигурации не найден
            logger.warning(f"Конфигурационный файл не найден: {config_path}")
            return default_config
            
        with open(config_path, 'r') as config_file:
            try:
                # Чтение и парсинг JSON-файла
                config = json.load(config_file)
                ldap_config = config.get('LDAP', default_config)  # Получение секции LDAP
                
                # Валидация конфигурации, если LDAP активирован
                if ldap_config.get('active'):
                    required_fields = ['domain_master', 'port', 'DN', 'username', 'password']
                    if not all(field in ldap_config for field in required_fields):
                        # Логирование ошибки о неполной конфигурации
                        logger.error("Неполная LDAP конфигурация при active=true")
                        return default_config
                
                # Логирование успешной загрузки конфигурации
                logger.info(f"Успешно загружена LDAP конфигурация. Active: {ldap_config.get('active')}")
                return ldap_config
                
            except json.JSONDecodeError as e:
                # Логирование ошибки парсинга JSON
                logger.error(f"Ошибка парсинга JSON: {str(e)}", exc_info=True)
                return default_config
                
    except Exception as e:
        # Логирование критической ошибки при чтении конфигурации
        logger.critical(f"Критическая ошибка чтения LDAP конфигурации: {str(e)}", exc_info=True)
        return default_config