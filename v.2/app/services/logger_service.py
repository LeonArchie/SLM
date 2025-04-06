import logging
import os
from logging.handlers import RotatingFileHandler
from services.config_service import get_config

def setup_logger():
    """Настройка детального логгера с параметрами из конфига"""
    config = get_config()
    log_config = config.get('LOG', {})
    
    log_path = log_config.get('app', 'app.log')
    max_bytes = log_config.get('max_bytes', 10*1024*1024)  # По умолчанию 10MB
    backup_count = log_config.get('backup_count', 5)  # По умолчанию 5 файлов
    log_level = log_config.get('log_level', 'INFO').upper()  # По умолчанию INFO
    
    # Создаём директорию если её нет
    os.makedirs(os.path.dirname(log_path), exist_ok=True)
    
    logger = logging.getLogger('app')
    
    try:
        logger.setLevel(getattr(logging, log_level))
    except AttributeError:
        logger.setLevel(logging.INFO)  # Если указан неверный уровень
    
    formatter = logging.Formatter(
        '%(asctime)s - %(name)s - %(levelname)s - %(message)s [%(filename)s:%(lineno)d]'
    )
    
    handler = RotatingFileHandler(
        filename=log_path,
        maxBytes=max_bytes,
        backupCount=backup_count,
        encoding='utf-8'
    )
    handler.setFormatter(formatter)
    logger.addHandler(handler)
    
    # Добавляем вывод в консоль для удобства разработки
    console_handler = logging.StreamHandler()
    console_handler.setFormatter(formatter)
    logger.addHandler(console_handler)
    
    logger.info(f"Logger configured. Path: {log_path}, Max size: {max_bytes} bytes, Backups: {backup_count}, Level: {log_level}")
    
    return logger