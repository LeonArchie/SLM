import logging
import os
from logging.handlers import RotatingFileHandler
from typing import Optional, Dict, Any

class LoggerService:
    """
    Сервис для управления логированием в приложении.
    Реализует паттерн Singleton для каждого имени логгера.
    
    Основные возможности:
    - Настройка нескольких логгеров с разными именами
    - Консольное и файловое логирование
    - Ротация лог-файлов по размеру
    - Динамическое изменение уровня логирования
    - Автоматическое создание директорий для логов
    - Потокобезопасная работа
    """
    
    # Словарь для хранения созданных логгеров (реализация паттерна Singleton)
    _loggers = {}

    @classmethod
    def get_logger(cls, name: str = 'app', config: Optional[Dict[str, Any]] = None) -> logging.Logger:
        """
        Возвращает настроенный экземпляр логгера.
        
        Параметры:
            name (str): Имя логгера. По умолчанию 'app'.
            config (dict): Конфигурация логирования из config.json. Должна содержать раздел 'log'.
            
        Возвращает:
            logging.Logger: Настроенный экземпляр логгера
            
        Пример конфигурации:
            "log": {
                "debug": true,                   # Включить debug режим
                "path": "/var/log/app/app.log",    # Путь к лог-файлу
                "size_log_file_max_bytes": 10485700,  # Макс. размер файла (10MB)
                "quantity_log_files": 5           # Кол-во ротируемых файлов
            }
        """
        # Если логгер с таким именем уже существует, возвращаем его
        if name in cls._loggers:
            cls._loggers[name].debug(f"Используется существующий логгер '{name}'")
            return cls._loggers[name]

        # Создаем новый логгер
        logger = logging.getLogger(name)
        
        # 1. Очистка предыдущих обработчиков (на случай повторной инициализации)
        logger.handlers.clear()
        
        # 2. Установка уровня логирования на основе конфигурации
        log_level = cls._determine_log_level(config)
        logger.setLevel(log_level)
        logger.debug(f"Создан новый логгер '{name}' с уровнем {logging.getLevelName(log_level)}")

        # 3. Создание форматтера для логов
        formatter = cls._create_formatter()
        
        # 4. Настройка консольного обработчика (всегда активен)
        cls._setup_console_handler(logger, formatter, log_level)
        
        # 5. Настройка файлового обработчика (если указан путь в конфиге)
        if config and config.get('log', {}).get('path'):
            cls._setup_file_handler(logger, formatter, log_level, config['log'])
        
        # Кэшируем созданный логгер
        cls._loggers[name] = logger
        
        logger.info(f"Логгер '{name}' успешно инициализирован")
        return logger

    @classmethod
    def _determine_log_level(cls, config: Optional[Dict[str, Any]]) -> int:
        """
        Определяет уровень логирования на основе конфигурации.
        
        Параметры:
            config (dict): Конфигурация приложения
            
        Возвращает:
            int: Уровень логирования (logging.DEBUG или logging.INFO)
        """
        if config and config.get('log', {}).get('debug', False):
            return logging.DEBUG
        return logging.INFO

    @classmethod
    def _create_formatter(cls) -> logging.Formatter:
        """
        Создает форматтер для логов.
        
        Формат сообщения:
        [2023-01-01 12:00:00] [app] [INFO] Сообщение [file.py:123]
        
        Возвращает:
            logging.Formatter: Сконфигурированный форматтер
        """
        return logging.Formatter(
            '[%(asctime)s] [%(name)s] [%(levelname)-5s] %(message)s '
            '[%(filename)s:%(lineno)d]',
            datefmt='%Y-%m-%d %H:%M:%S'
        )

    @classmethod
    def _setup_console_handler(cls, 
                             logger: logging.Logger,
                             formatter: logging.Formatter,
                             log_level: int) -> None:
        """
        Настраивает консольный обработчик логов.
        
        Параметры:
            logger: Экземпляр логгера
            formatter: Форматтер для сообщений
            log_level: Уровень логирования
        """
        console_handler = logging.StreamHandler()
        console_handler.setFormatter(formatter)
        console_handler.setLevel(log_level)
        logger.addHandler(console_handler)
        logger.debug("Консольный обработчик логов успешно настроен")

    @classmethod
    def _setup_file_handler(cls,
                          logger: logging.Logger,
                          formatter: logging.Formatter,
                          log_level: int,
                          log_config: Dict[str, Any]) -> None:
        """
        Настраивает файловый обработчик логов с ротацией.
        
        Параметры:
            logger: Экземпляр логгера
            formatter: Форматтер для сообщений
            log_level: Уровень логирования
            log_config: Конфигурация логирования
        """
        log_path = log_config['path']
        
        try:
            # Создание директории для логов если необходимо
            log_dir = os.path.dirname(log_path)
            if log_dir and not os.path.exists(log_dir):
                os.makedirs(log_dir, exist_ok=True)
                logger.debug(f"Создана директория для логов: {log_dir}")

            # Настройка ротирующего обработчика
            file_handler = RotatingFileHandler(
                filename=log_path,
                maxBytes=log_config.get('size_log_file_max_bytes', 10*1024*1024),  # 10MB по умолчанию
                backupCount=log_config.get('quantity_log_files', 5),  # 5 файлов по умолчанию
                encoding='utf-8'
            )
            file_handler.setFormatter(formatter)
            file_handler.setLevel(log_level)
            logger.addHandler(file_handler)
            
            logger.info(f"Файловое логирование настроено. Путь: {log_path}, "
                      f"Макс. размер: {file_handler.maxBytes} байт, "
                      f"Кол-во файлов: {file_handler.backupCount}")
        except PermissionError as e:
            logger.error(f"Ошибка прав доступа при настройке файлового логирования: {str(e)}")
        except OSError as e:
            logger.error(f"Ошибка файловой системы при настройке логирования: {str(e)}")
        except Exception as e:
            logger.error(f"Неожиданная ошибка при настройке файлового логирования: {str(e)}")

# Инициализация глобального логгера при загрузке модуля
try:
    # Создаем базовый логгер без конфигурации (уровень INFO по умолчанию)
    logger = LoggerService.get_logger('core')
    logger.info("Модуль LoggerService успешно инициализирован")
except Exception as e:
    print(f"КРИТИЧЕСКАЯ ОШИБКА: Не удалось инициализировать логгер: {str(e)}")
    raise