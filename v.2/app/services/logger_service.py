import logging
import os
from logging.handlers import RotatingFileHandler
from typing import Optional, Dict, Any

class LoggerService:
    _loggers = {}

    @classmethod
    def get_logger(cls, name: str = 'app', config: Optional[Dict[str, Any]] = None) -> logging.Logger:
        """Возвращает настроенный экземпляр логгера с опциональным файловым обработчиком"""
        # Проверка наличия уже созданного логгера для указанного имени
        if name in cls._loggers:
            return cls._loggers[name]

        # Создание нового логгера
        logger = logging.getLogger(name)
        logger.setLevel(logging.DEBUG)

        # Создание форматтера для логов
        formatter = logging.Formatter(
            '%(asctime)s | %(name)s | %(levelname)-8s | %(message)s '
            '[%(filename)s:%(lineno)d]'
        )

        # Консольный обработчик (всегда включен)
        console_handler = logging.StreamHandler()
        console_handler.setFormatter(formatter)
        console_handler.setLevel(logging.DEBUG)
        logger.addHandler(console_handler)

        # Файловый обработчик (если предоставлены настройки конфигурации)
        if config and 'LOG' in config:
            try:
                log_config = config['LOG']
                log_dir = os.path.dirname(log_config.get('app', 'app.log'))
                
                try:
                    # Создание директории для логов, если она не существует
                    if log_dir and not os.path.exists(log_dir):
                        os.makedirs(log_dir, exist_ok=True)
                except OSError as e:
                    # Логирование ошибки создания директории
                    logger.error(f"Не удалось создать директорию для логов: {str(e)}")
                    raise

                try:
                    # Настройка файлового обработчика с ротацией
                    file_handler = RotatingFileHandler(
                        filename=log_config.get('app', 'app.log'),  # Имя файла лога
                        maxBytes=log_config.get('max_bytes', 10*1024*1024),  # Максимальный размер файла
                        backupCount=log_config.get('backup_count', 5),  # Количество резервных копий
                        encoding='utf-8'  # Кодировка
                    )
                    file_handler.setFormatter(formatter)
                    
                    try:
                        # Установка уровня логирования из конфигурации
                        file_handler.setLevel(getattr(logging, log_config.get('log_level', 'INFO').upper()))
                    except AttributeError:
                        # Если уровень логирования некорректен, используется INFO по умолчанию
                        file_handler.setLevel(logging.INFO)
                        logger.warning(f"Некорректный уровень логирования в конфигурации, используется INFO по умолчанию")
                    
                    # Добавление файлового обработчика к логгеру
                    logger.addHandler(file_handler)
                    logger.info(f"Файловое логирование настроено: {log_config.get('app')}")

                except Exception as e:
                    # Логирование ошибки настройки файлового обработчика
                    logger.error(f"Не удалось настроить файловый обработчик: {str(e)}")
                    raise

            except Exception as e:
                # Логирование ошибки конфигурации файлового логирования
                logger.error(f"Ошибка конфигурации файлового логирования: {str(e)}")
                # Продолжение работы только с консольным логированием

        # Сохранение созданного логгера в кэше
        cls._loggers[name] = logger
        return logger

# Глобальный минимальный экземпляр логгера
try:
    logger = LoggerService.get_logger('core')
except Exception as e:
    # Вывод критической ошибки при инициализации логгера
    print(f"Критическая ошибка: Не удалось инициализировать логгер: {str(e)}")
    raise