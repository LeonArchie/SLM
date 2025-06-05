import json
import os
import time
import threading
from typing import Any, Dict, Optional, Union
from dotenv import load_dotenv
from pathlib import Path
from internal.logger_service import LoggerService

# Инициализация логгера с указанием имени модуля для удобства трассировки
logger = LoggerService.get_logger('app.config.service')

class ConfigService:
    """
    Сервис управления конфигурацией приложения.
    Реализует паттерн Singleton для обеспечения единой точки доступа к настройкам.
    Поддерживает загрузку конфигурации из файла config.json и переменных окружения .env.
    Автоматически обновляет конфигурацию через заданные интервалы времени.
    """
    
    _instance = None                      # Экземпляр Singleton
    _file_config: Dict[str, Any] = {}     # Кэш конфигурации из config.json
    _env_vars: Dict[str, str] = {}        # Кэш переменных окружения (загружаются как есть, без структуры)
    _last_reload_time: float = 0          # Время последней перезагрузки (timestamp)
    _reload_interval: int = 60            # Интервал перезагрузки в секундах (по умолчанию 60)
    _lock = threading.Lock()              # Блокировка для обеспечения потокобезопасности
    _initialized: bool = False            # Флаг завершения инициализации

    def __new__(cls):
        """
        Реализация паттерна Singleton.
        Гарантирует создание только одного экземпляра класса.
        """
        if cls._instance is None:
            logger.debug("Попытка создания нового экземпляра ConfigService")
            with cls._lock:
                # Двойная проверка для защиты от состояния гонки
                if cls._instance is None:
                    cls._instance = super(ConfigService, cls).__new__(cls)
                    logger.debug("Экземпляр ConfigService успешно создан")
        return cls._instance

    def _initialize(self):
        """
        Инициализация сервиса конфигурации.
        Выполняет первичную загрузку конфигурации и запускает фоновый перезагрузчик.
        """
        with self._lock:
            if not self._initialized:
                logger.info("Начало инициализации ConfigService")
                
                try:
                    self._load_file_config()
                    self._load_env_vars()
                    self._validate_essential_config()
                    self._start_reloader()
                    
                    self._initialized = True
                    logger.info("ConfigService успешно инициализирован")
                    logger.debug(f"Текущее время: {time.ctime()}, "
                                f"интервал перезагрузки: {self._reload_interval} сек, "
                                f"путь к корню: {self._get_root_path()}")
                except Exception as e:
                    logger.error(f"Критическая ошибка инициализации ConfigService: {str(e)}", exc_info=True)
                    raise

    def _get_root_path(self) -> Path:
        """
        Определяет абсолютный путь к корневой директории приложения.
        
        Returns:
            Path: Абсолютный путь к корневой директории
            
        Пример:
            Если файл config_service.py находится в /app/services,
            то корневой путь будет /app
        """
        return Path(os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))

    def _load_file_config(self):
        """
        Загружает и парсит конфигурационный файл config.json.
        Выполняет проверки:
        1. Существование файла
        2. Права на чтение
        3. Валидность JSON
        
        Raises:
            FileNotFoundError: Если файл не существует
            PermissionError: Если нет прав на чтение
            json.JSONDecodeError: При ошибке парсинга JSON
        """
        config_path = self._get_root_path() / 'config.json'
        logger.debug(f"Попытка загрузки конфигурационного файла: {config_path}")

        try:
            with self._lock:
                logger.debug(f"Проверка существования файла: {config_path}")
                if not config_path.exists():
                    error_msg = f"Файл конфигурации не найден по пути: {config_path}"
                    logger.error(error_msg)
                    raise FileNotFoundError(error_msg)

                logger.debug(f"Проверка прав доступа к файлу: {config_path}")
                if not os.access(config_path, os.R_OK):
                    error_msg = f"Отсутствуют права на чтение файла: {config_path}"
                    logger.error(error_msg)
                    raise PermissionError(error_msg)

                logger.debug(f"Чтение и парсинг файла: {config_path}")
                with open(config_path, 'r', encoding='utf-8') as f:
                    new_config = json.load(f)
                    self._file_config = new_config
                    self._update_reload_interval()
                    self._last_reload_time = time.time()
                    
                    logger.info(f"Конфигурационный файл успешно загружен. "
                              f"Найдено {len(self._file_config)} секций")
                    logger.debug(f"Содержимое конфигурации: {json.dumps(self._file_config, indent=2)}")

        except json.JSONDecodeError as e:
            logger.error(f"Ошибка парсинга JSON в строке {e.lineno}, колонка {e.colno}: {e.msg}",
                       exc_info=True)
            raise
        except Exception as e:
            logger.error(f"Критическая ошибка загрузки конфигурации: {str(e)}", exc_info=True)
            raise

    def _load_env_vars(self):
        """
        Загружает переменные окружения из файла .env.
        Сохраняет все переменные в плоском словаре без структурирования.
        """
        env_path = self._get_root_path() / '.env'
        logger.debug(f"Попытка загрузки переменных окружения из: {env_path}")

        try:
            with self._lock:
                logger.debug(f"Загрузка переменных окружения из {env_path}")
                load_dotenv(dotenv_path=env_path, verbose=True)
                
                # Получаем все переменные окружения
                self._env_vars = {k: v for k, v in os.environ.items()}
                self._last_reload_time = time.time()
                
                logger.info(f"Загружено {len(self._env_vars)} переменных окружения")
                logger.debug(f"Список переменных окружения: {list(self._env_vars.keys())}")
                
        except Exception as e:
            logger.error(f"Ошибка загрузки переменных окружения: {str(e)}", exc_info=True)
            raise

    def _update_reload_interval(self):
        """
        Обновляет интервал перезагрузки конфигурации из файла конфигурации.
        Если значение не найдено или невалидно, используется значение по умолчанию (60 сек).
        """
        try:
            new_interval = self._file_config.get('config', {}).get('reload_second', 60)
            
            if not isinstance(new_interval, int) or new_interval <= 0:
                logger.warning(f"Некорректный интервал перезагрузки: {new_interval}. "
                              "Используется значение по умолчанию (60 сек)")
                new_interval = 60
                
            self._reload_interval = new_interval
            logger.debug(f"Интервал перезагрузки установлен: {self._reload_interval} сек")
            
        except Exception as e:
            logger.warning(f"Не удалось обновить интервал перезагрузки: {str(e)}. "
                          "Используется значение по умолчанию (60 сек)")
            self._reload_interval = 60

    def _validate_essential_config(self):
        """
        Проверяет наличие обязательных параметров конфигурации.
        В текущей реализации проверяет только наличие ключевых переменных окружения.
        """
        logger.debug("Начало валидации обязательных параметров конфигурации")
        
        required_vars = ['FLASK_KEY', 'JWT1_KEY']
        missing_vars = [var for var in required_vars if var not in self._env_vars]
        
        if missing_vars:
            error_msg = f"Отсутствуют обязательные переменные окружения: {', '.join(missing_vars)}"
            logger.error(error_msg)
            raise ValueError(error_msg)
            
        logger.info("Проверка обязательных параметров конфигурации завершена успешно")

    def _start_reloader(self):
        """
        Запускает фоновый поток для периодической перезагрузки конфигурации.
        Поток работает в режиме демона и автоматически завершается при выходе из приложения.
        """
        def reloader():
            logger.info(f"Запущен перезагрузчик конфигурации с интервалом {self._reload_interval} сек")
            
            while True:
                try:
                    time.sleep(self._reload_interval)
                    logger.debug(f"Плановый запуск перезагрузки конфигурации. "
                               f"Прошло {self._reload_interval} сек с последней загрузки")
                    
                    self._load_file_config()
                    self._load_env_vars()
                    
                    logger.info(f"Конфигурация успешно перезагружена в {time.ctime()}")
                except Exception as e:
                    logger.error(f"Ошибка в потоке перезагрузки: {str(e)}", exc_info=True)
                    # Продолжаем работу после ошибки, следующая попытка будет через интервал

        # Создаем и запускаем поток-демон
        thread = threading.Thread(
            target=reloader,
            name="ConfigReloader",
            daemon=True
        )
        thread.start()
        
        logger.info(f"Фоновый перезагрузчик конфигурации запущен. "
                   f"ID потока: {thread.ident}, имя: {thread.name}")

    def get_file_config(self, key_path: Optional[str] = None) -> Any:
        """
        Получает значение из файловой конфигурации (config.json).
        
        Args:
            key_path: Путь к значению в формате 'секция.ключ' (опционально)
            
        Returns:
            Запрошенное значение или вся конфигурация, если key_path не указан
            
        Raises:
            KeyError: Если запрошенный ключ не найден
            ValueError: При других ошибках
        """
        try:
            logger.debug(f"Запрос файловой конфигурации для ключа: {key_path or 'все значения'}")
            
            if key_path is None:
                logger.info("Возвращена полная файловая конфигурация")
                return self._file_config

            # Поиск значения по вложенным ключам
            keys = key_path.split('.')
            value = self._file_config
            for key in keys:
                value = value[key]
                
            logger.info(f"Получено значение для '{key_path}' из файловой конфигурации")
            logger.debug(f"Тип значения: {type(value)}, содержание: {value}")
            return value
            
        except KeyError as e:
            error_msg = f"Ключ не найден в файловой конфигурации: {key_path}"
            logger.warning(error_msg)
            raise KeyError(error_msg)
        except Exception as e:
            error_msg = f"Ошибка получения файловой конфигурации: {str(e)}"
            logger.error(error_msg, exc_info=True)
            raise ValueError(error_msg)

    def get_env_var(self, var_name: str, default: Optional[str] = None) -> Optional[str]:
        """
        Получает значение переменной окружения.
        
        Args:
            var_name: Имя переменной окружения
            default: Значение по умолчанию, если переменная не найдена (опционально)
            
        Returns:
            Значение переменной или default, если переменная не найдена
            
        Raises:
            ValueError: Если переменная не найдена и не указано значение по умолчанию
        """
        try:
            logger.debug(f"Запрос переменной окружения: {var_name}")
            
            value = self._env_vars.get(var_name, default)
            
            if value is None:
                error_msg = f"Переменная окружения не найдена и нет значения по умолчанию: {var_name}"
                logger.error(error_msg)
                raise ValueError(error_msg)
                
            logger.info(f"Получено значение для переменной {var_name}")
            logger.debug(f"Значение: {value}")
            return value
            
        except Exception as e:
            logger.error(f"Ошибка получения переменной окружения: {str(e)}", exc_info=True)
            raise

# Инициализация глобального экземпляра ConfigService
try:
    config_service = ConfigService()
    config_service._initialize()
    logger.info("Модуль ConfigService полностью инициализирован и готов к использованию")
except Exception as e:
    logger.error(f"ФАТАЛЬНАЯ ОШИБКА: Не удалось инициализировать ConfigService: {str(e)}", exc_info=True)
    raise