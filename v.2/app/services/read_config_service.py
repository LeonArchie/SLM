import json
import os
from typing import Dict, Any
from services.logger_service import LoggerService

# Инициализация базового логгера без зависимостей
logger = LoggerService.get_logger('app.config')

def get_config() -> Dict[str, Any]:
    """Загрузка и проверка конфигурации приложения"""
    # Конфигурация по умолчанию
    default_config = {
        "LDAP": {"active": False},  # Настройки LDAP (по умолчанию отключены)
        "LOG": {  # Настройки логирования
            "app": "app.log",
            "max_bytes": 10485760,  # Максимальный размер лог-файла (10 МБ)
            "backup_count": 5,  # Количество ротаций логов
            "log_level": "INFO"  # Уровень логирования
        },
        "db": {  # Настройки базы данных
            "host": "localhost",  # Хост базы данных
            "port": 5432,  # Порт базы данных
            "name": "default_db",  # Имя базы данных
            "user": "default_user",  # Пользователь базы данных
            "password": ""  # Пароль пользователя
        },
        "flask": {  # Настройки Flask
            "SECRET_KEY": "cfd168c9ab98a3a916992f2b8968099504405f697938e02b363b2e4a71774248d8f1b0058ad142a0ad0fc7a741dcea72bb8ea1f7203b97112a6446b016f1a4f3e0c55335fbf730738154d772dc38edd29e94e7e17d9c58d23ec7d97ae3c07e7bfd62d039ea6d6bcd93fdf7a7672c07e85d3e45523a3f3a205382bddcfd",  # Секретный ключ Flask
            "JWT": {  # Настройки JWT
                "SECRET_KEY": "6c24d765457ff3db5a0bbecd801f161b2c9de40b0c3cbd93d8d7702bc5b62276249ba1382424af45aa314e5f065f7c4355f8fadf3f439a9fdce6ca2142d13bfc9ee065e0e538b986c1706c4c5c3a4b342e9fa770fdb39162abefa716e099ba20ddf7c00e37ee0800e6dea68ff9a9473d2d1b5067fb5d15d015d0a838e0",  # Секретный ключ для JWT
                "ACCESS_EXPIRES": 3600,  # Время жизни access-токена (в секундах)
                "REFRESH_EXPIRES": 86400  # Время жизни refresh-токена (в секундах)
            }
        },
        "version": {  # Информация о версии приложения
            "current_version": "0.0.0"
        }
    }

    try:
        # Определение пути к файлу конфигурации
        config_path = os.path.join(os.path.dirname(__file__), '..', 'config.json')
        logger.debug(f"Загрузка конфигурации из: {config_path}")
        
        # Если файл конфигурации не существует, используем конфигурацию по умолчанию
        if not os.path.exists(config_path):
            logger.warning("Файл конфигурации не найден, используются настройки по умолчанию")
            return default_config
            
        # Чтение пользовательской конфигурации из файла
        with open(config_path, 'r') as f:
            user_config = json.load(f)
            # Объединение конфигураций: значения из файла переопределяют значения по умолчанию
            merged_config = {**default_config, **user_config}
            
            # Проверка наличия обязательных разделов
            required_sections = ['flask', 'db']
            for section in required_sections:
                if section not in merged_config:
                    logger.error(f"Отсутствует обязательный раздел конфигурации: {section}")
                    raise ValueError(f"Отсутствует раздел конфигурации: {section}")
            
            # Проверка секретных ключей на использование значений по умолчанию
            if (merged_config['flask']['SECRET_KEY'] == default_config['flask']['SECRET_KEY'] or
                merged_config['flask']['JWT']['SECRET_KEY'] == default_config['flask']['JWT']['SECRET_KEY']):
                logger.warning("Используются секретные ключи по умолчанию! Это небезопасно!")
            
            # Логирование успешной загрузки конфигурации
            logger.info("Конфигурация успешно загружена")
            return merged_config
            
    except json.JSONDecodeError as e:
        # Логирование ошибки декодирования JSON
        logger.error(f"Ошибка декодирования JSON в конфигурации: {str(e)}")
        return default_config
    except Exception as e:
        # Логирование критической ошибки при загрузке конфигурации
        logger.critical(f"Не удалось загрузить конфигурацию: {str(e)}", exc_info=True)
        raise