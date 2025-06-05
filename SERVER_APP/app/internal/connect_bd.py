import time
import psycopg2
import threading
from internal.config_service import config_service
from internal.logger_service import LoggerService
from psycopg2 import pool
from typing import Dict, Optional, Tuple, Any, Generator
from contextlib import contextmanager

# Инициализация логгера с указанием имени модуля для удобства трассировки
logger = LoggerService.get_logger('app.db')

class DatabaseService:
    """
    Сервис для управления подключениями к базе данных с поддержкой репликации.
    Реализует:
    - Пул соединений для мастер-базы и реплики
    - Автоматическое переключение между мастером и репликой
    - Фоновую проверку доступности БД
    - Retry-логику при подключении и выполнении запросов
    """
    
    _master_pool: Optional[psycopg2.pool.ThreadedConnectionPool] = None
    _replica_pool: Optional[psycopg2.pool.ThreadedConnectionPool] = None
    _current_mode: Optional[str] = None
    _replication_config: Dict[str, Any] = None
    _max_retry_attempts: int = 5
    _retry_delay: int = 5
    _health_check_interval: int = 60
    _stop_health_check: bool = False
    _replica_available: bool = False
    _master_available: bool = False

    @classmethod
    def initialize(cls) -> None:
        """
        Инициализирует сервис базы данных:
        1. Загружает конфигурацию из ConfigService
        2. Инициализирует пулы соединений
        3. Запускает фоновую проверку доступности
        """
        try:
            logger.info("Начало инициализации DatabaseService")
            
            # Загрузка конфигурации
            cls._load_configuration()
            
            # Получение параметров подключения
            db_config = cls._get_db_config()
            logger.debug(f"Конфигурация подключения к БД загружена: {db_config}")
            
            # Инициализация пулов соединений
            cls._init_master_pool(db_config['master'])
            cls._master_available = True
            
            if cls._replication_config['replication'] in ['read_only', 'read_write']:
                cls._init_replica_pool(db_config['replica'])
                cls._replica_available = cls._is_pool_healthy(cls._replica_pool)
            
            cls._current_mode = 'master'
            
            # Запуск фоновой проверки доступности
            health_check_thread = threading.Thread(
                target=cls._health_check_loop, 
                name="DBHealthCheck",
                daemon=True
            )
            health_check_thread.start()
            
            logger.info(
                f"DatabaseService успешно инициализирован. "
                f"Режим репликации: {cls._replication_config['replication']}, "
                f"Мастер: {'доступен' if cls._master_available else 'недоступен'}, "
                f"Реплика: {'доступна' if cls._replica_available else 'недоступна'}"
            )
            
        except Exception as e:
            logger.error(
                f"Критическая ошибка инициализации DatabaseService: {str(e)}\n"
                f"Конфигурация репликации: {cls._replication_config}",
                exc_info=True
            )
            raise

    @classmethod
    def _load_configuration(cls) -> None:
        """Загружает и валидирует конфигурацию подключения к БД."""
        try:
            cls._replication_config = config_service.get_file_config('database')
            logger.debug(f"Загружена конфигурация БД: {cls._replication_config}")
            
            # Установка параметров retry
            cls._max_retry_attempts = cls._replication_config.get('max_retry_attempts', 5)
            cls._retry_delay = cls._replication_config.get('retry_delay_second', 5)
            cls._health_check_interval = cls._replication_config.get('health_check_second', 60)
            
            logger.info(
                f"Параметры подключения: "
                f"Макс. попыток: {cls._max_retry_attempts}, "
                f"Задержка между попытками: {cls._retry_delay} сек, "
                f"Интервал проверки здоровья: {cls._health_check_interval} сек"
            )
            
        except Exception as e:
            logger.error("Ошибка загрузки конфигурации БД", exc_info=True)
            raise

    @classmethod
    def shutdown(cls) -> None:
        """
        Корректно завершает работу сервиса:
        1. Останавливает фоновые проверки
        2. Закрывает все пулы соединений
        """
        logger.info("Начало завершения работы DatabaseService")
        cls._stop_health_check = True
        
        if cls._master_pool:
            cls._master_pool.closeall()
            logger.info("Пул соединений с мастер-базой закрыт")
        
        if cls._replica_pool:
            cls._replica_pool.closeall()
            logger.info("Пул соединений с репликой закрыт")
        
        logger.info("DatabaseService успешно завершил работу")

    @classmethod
    def _health_check_loop(cls) -> None:
        """
        Фоновая проверка доступности БД:
        1. Проверяет доступность мастер-базы
        2. Проверяет доступность реплики (если настроена)
        3. Логирует изменения состояния
        """
        logger.info(
            f"Запуск фоновой проверки состояния БД с интервалом {cls._health_check_interval} сек"
        )
        
        while not cls._stop_health_check:
            try:
                # Проверка мастера
                master_healthy = cls._is_pool_healthy(cls._master_pool)
                if master_healthy != cls._master_available:
                    status = "доступен" if master_healthy else "недоступен"
                    logger.warning(f"Состояние мастер-базы изменилось: {status}")
                    cls._master_available = master_healthy
                
                # Проверка реплики (если настроена)
                if cls._replication_config['replication'] in ['read_only', 'read_write'] and cls._replica_pool:
                    replica_healthy = cls._is_pool_healthy(cls._replica_pool)
                    if replica_healthy != cls._replica_available:
                        status = "доступна" if replica_healthy else "недоступна"
                        logger.warning(f"Состояние реплики изменилось: {status}")
                        cls._replica_available = replica_healthy
                
                logger.debug(
                    f"Текущее состояние: "
                    f"Мастер: {'доступен' if cls._master_available else 'недоступен'}, "
                    f"Реплика: {'доступна' if cls._replica_available else 'недоступна'}"
                )
                
            except Exception as e:
                logger.error(f"Ошибка при проверке состояния БД: {str(e)}", exc_info=True)
            
            time.sleep(cls._health_check_interval)

    @classmethod
    def _is_pool_healthy(cls, pool: Optional[psycopg2.pool.ThreadedConnectionPool]) -> bool:
        """
        Проверяет доступность пула соединений.
        
        Args:
            pool: Пул соединений для проверки
            
        Returns:
            bool: True если пул доступен, False в противном случае
        """
        if not pool:
            logger.debug("Пул соединений не инициализирован")
            return False
            
        try:
            with cls._get_connection_from_pool(pool) as conn:
                with conn.cursor() as cur:
                    cur.execute("SELECT 1")
                    result = cur.fetchone()
                    if result and result[0] == 1:
                        logger.debug("Пул соединений доступен")
                        return True
            logger.debug("Неверный результат проверки доступности пула")
            return False
        except Exception as e:
            logger.debug(f"Пул соединений не доступен: {str(e)}")
            return False

    @classmethod
    def _get_db_config(cls) -> Dict[str, Dict[str, Any]]:
        """
        Формирует конфигурацию подключения из переменных окружения.
        
        Returns:
            Dict: Конфигурация с ключами 'master' и 'replica'
        """
        logger.debug("Загрузка конфигурации подключения из переменных окружения")
        
        try:
            config = {
                'master': {
                    'host': config_service.get_env_var('DB_MASTER_HOST'),
                    'port': int(config_service.get_env_var('DB_MASTER_PORT')),
                    'name': config_service.get_env_var('DB_NAME'),
                    'user': config_service.get_env_var('DB_MASTER_USER'),
                    'password': config_service.get_env_var('DB_MASTER_PASS')
                },
                'replica': {
                    'host': config_service.get_env_var('DB_REPLICA_HOST'),
                    'port': int(config_service.get_env_var('DB_REPLICA_PORT')),
                    'name': config_service.get_env_var('DB_NAME'),
                    'user': config_service.get_env_var('DB_REPLICA_USER'),
                    'password': config_service.get_env_var('DB_REPLICA_PASS')
                }
            }
            
            logger.debug(
                f"Конфигурация подключения: "
                f"Мастер: {config['master']['host']}:{config['master']['port']}, "
                f"Реплика: {config['replica']['host']}:{config['replica']['port']}"
            )
            
            return config
        except Exception as e:
            logger.error("Ошибка загрузки конфигурации подключения", exc_info=True)
            raise

    @classmethod
    def _init_master_pool(cls, config: Dict[str, Any]) -> None:
        """
        Инициализирует пул соединений с мастер-базой с retry-логикой.
        
        Args:
            config: Конфигурация подключения
            
        Raises:
            RuntimeError: Если подключение не удалось после всех попыток
        """
        logger.info(
            f"Инициализация подключения к мастер-базе: "
            f"{config['host']}:{config['port']}/{config['name']}"
        )
        
        for attempt in range(cls._max_retry_attempts):
            try:
                logger.debug(
                    f"Попытка {attempt + 1}/{cls._max_retry_attempts}: "
                    f"Создание пула для мастер-базы"
                )
                
                cls._master_pool = psycopg2.pool.ThreadedConnectionPool(
                    minconn=cls._replication_config.get('pool_min_connect', 1),
                    maxconn=cls._replication_config.get('pool_max_connect', 50),
                    host=config['host'],
                    port=config['port'],
                    dbname=config['name'],
                    user=config['user'],
                    password=config['password']
                )
                
                # Проверка подключения
                with cls._get_connection_from_pool(cls._master_pool) as conn:
                    with conn.cursor() as cur:
                        cur.execute("SELECT version()")
                        db_version = cur.fetchone()
                        logger.info(f"Подключено к мастер-базе. Версия: {db_version[0]}")
                
                logger.info(
                    f"Пул соединений с мастер-базой успешно создан. "
                    f"Min connections: {cls._replication_config.get('pool_min_connect', 1)}, "
                    f"Max connections: {cls._replication_config.get('pool_max_connect', 50)}"
                )
                return
                
            except Exception as e:
                if attempt == cls._max_retry_attempts - 1:
                    logger.error(
                        f"Не удалось подключиться к мастер-базе после {cls._max_retry_attempts} попыток. "
                        f"Последняя ошибка: {str(e)}",
                        exc_info=True
                    )
                    raise RuntimeError(f"Не удалось подключиться к мастер-базе: {str(e)}")
                
                logger.warning(
                    f"Попытка {attempt + 1}/{cls._max_retry_attempts}: "
                    f"Ошибка подключения к мастер-базе: {str(e)}. "
                    f"Повторная попытка через {cls._retry_delay} сек"
                )
                time.sleep(cls._retry_delay)

    @classmethod
    def _init_replica_pool(cls, config: Dict[str, Any]) -> None:
        """
        Инициализирует пул соединений с репликой с retry-логикой.
        
        Args:
            config: Конфигурация подключения
            
        Note:
            В режиме 'read_only' ошибки подключения к реплике не приводят к исключению
        """
        logger.info(
            f"Инициализация подключения к реплике: "
            f"{config['host']}:{config['port']}/{config['name']}"
        )
        
        for attempt in range(cls._max_retry_attempts):
            try:
                logger.debug(
                    f"Попытка {attempt + 1}/{cls._max_retry_attempts}: "
                    f"Создание пула для реплики"
                )
                
                cls._replica_pool = psycopg2.pool.ThreadedConnectionPool(
                    minconn=cls._replication_config.get('pool_min_connect', 1),
                    maxconn=cls._replication_config.get('pool_max_connect', 50),
                    host=config['host'],
                    port=config['port'],
                    dbname=config['name'],
                    user=config['user'],
                    password=config['password']
                )
                
                # Проверка подключения
                with cls._get_connection_from_pool(cls._replica_pool) as conn:
                    with conn.cursor() as cur:
                        cur.execute("SELECT version()")
                        db_version = cur.fetchone()
                        logger.info(f"Подключено к реплике. Версия: {db_version[0]}")
                
                logger.info(
                    f"Пул соединений с репликой успешно создан. "
                    f"Min connections: {cls._replication_config.get('pool_min_connect', 1)}, "
                    f"Max connections: {cls._replication_config.get('pool_max_connect', 50)}"
                )
                return
                
            except Exception as e:
                if attempt == cls._max_retry_attempts - 1:
                    logger.error(
                        f"Не удалось подключиться к реплике после {cls._max_retry_attempts} попыток. "
                        f"Последняя ошибка: {str(e)}",
                        exc_info=True
                    )
                    if cls._replication_config['replication'] == 'read_write':
                        raise RuntimeError(f"Не удалось подключиться к реплике в режиме read_write: {str(e)}")
                    return
                
                logger.warning(
                    f"Попытка {attempt + 1}/{cls._max_retry_attempts}: "
                    f"Ошибка подключения к реплике: {str(e)}. "
                    f"Повторная попытка через {cls._retry_delay} сек"
                )
                time.sleep(cls._retry_delay)

    @classmethod
    @contextmanager
    def _get_connection_from_pool(cls, pool: psycopg2.pool.ThreadedConnectionPool) -> Generator[psycopg2.extensions.connection, None, None]:
        """
        Менеджер контекста для получения соединения из пула.
        
        Args:
            pool: Пул соединений
            
        Yields:
            Соединение с БД
            
        Raises:
            RuntimeError: Если пул не инициализирован
        """
        if not pool:
            logger.error("Попытка получить соединение из неинициализированного пула")
            raise RuntimeError("Пул соединений не инициализирован")
            
        conn = None
        try:
            conn = pool.getconn()
            logger.debug(f"Получено соединение из пула (ID: {id(conn)})")
            yield conn
        except Exception as e:
            if conn:
                logger.error(
                    f"Ошибка при работе с соединением (ID: {id(conn)}): {str(e)}. "
                    f"Выполняется rollback",
                    exc_info=True
                )
                conn.rollback()
            raise
        finally:
            if conn:
                pool.putconn(conn)
                logger.debug(f"Соединение (ID: {id(conn)}) возвращено в пул")

    @classmethod
    @contextmanager
    def get_connection(cls, read_only: bool = False) -> Generator[psycopg2.extensions.connection, None, None]:
        """
        Основной метод для получения соединения с БД.
        
        Args:
            read_only: Флаг, указывающий на необходимость использования реплики
            
        Yields:
            Соединение с БД
            
        Raises:
            RuntimeError: Если нет доступных соединений
        """
        if cls._master_pool is None:
            logger.error("Попытка получить соединение до инициализации пула")
            raise RuntimeError("Пул соединений с базой данных не инициализирован")
        
        # Определяем какой пул использовать
        use_replica = (
            read_only and 
            cls._replica_pool is not None and 
            cls._replica_available and
            cls._replication_config['replication'] != 'false'
        )
        
        pool_to_use = cls._replica_pool if use_replica else cls._master_pool
        
        # Если мастер недоступен и реплика доступна, используем реплику
        if not cls._master_available and cls._replica_available and cls._replication_config['replication'] != 'false':
            pool_to_use = cls._replica_pool
            cls._current_mode = 'replica'
            logger.warning("Мастер недоступен, переключение на реплику")
        
        logger.debug(
            f"Запрос соединения (read_only={read_only}). "
            f"Будет использован: {'реплика' if pool_to_use == cls._replica_pool else 'мастер'}"
        )
        
        for attempt in range(cls._max_retry_attempts):
            try:
                with cls._get_connection_from_pool(pool_to_use) as conn:
                    logger.debug(f"Соединение успешно получено (ID: {id(conn)})")
                    yield conn
                return
            except Exception as e:
                if attempt == cls._max_retry_attempts - 1:
                    logger.error(
                        f"Не удалось получить соединение после {cls._max_retry_attempts} попыток. "
                        f"Последняя ошибка: {str(e)}",
                        exc_info=True
                    )
                    raise RuntimeError(f"Не удалось получить соединение с БД: {str(e)}")
                
                logger.warning(
                    f"Попытка {attempt + 1}/{cls._max_retry_attempts}: "
                    f"Ошибка получения соединения: {str(e)}. "
                    f"Повторная попытка через {cls._retry_delay} сек"
                )
                time.sleep(cls._retry_delay)

    @classmethod
    def execute_write_operation(cls, query: str, params: Optional[Tuple] = None) -> None:
        """
        Выполняет операцию записи в БД.
        
        Args:
            query: SQL-запрос
            params: Параметры запроса
            
        Raises:
            RuntimeError: Если операция записи невозможна в текущем режиме
        """
        logger.debug(
            f"Выполнение операции записи. "
            f"Запрос: {query}, "
            f"Параметры: {params}"
        )
        
        if cls._replication_config['replication'] == 'read_only' and cls._current_mode == 'replica':
            error_msg = "Операция записи невозможна в режиме read_only с репликой"
            logger.error(error_msg)
            raise RuntimeError(error_msg)
        
        if not cls._master_available:
            error_msg = "Мастер-база недоступна для операций записи"
            logger.error(error_msg)
            raise RuntimeError(error_msg)
        
        try:
            with cls.get_connection(read_only=False) as conn:
                with conn.cursor() as cur:
                    logger.debug(f"Выполнение запроса: {query}")
                    cur.execute(query, params)
                    conn.commit()
                    logger.info("Операция записи успешно выполнена")
        except Exception as e:
            logger.error(
                f"Ошибка выполнения операции записи: {str(e)}\n"
                f"Запрос: {query}\n"
                f"Параметры: {params}",
                exc_info=True
            )
            raise

# Инициализация сервиса при импорте
try:
    DatabaseService.initialize()
    logger.info("Модуль DatabaseService полностью инициализирован и готов к использованию")
except Exception as e:
    logger.error(
        f"ФАТАЛЬНАЯ ОШИБКА: Не удалось инициализировать DatabaseService: {str(e)}\n"
        f"Тип ошибки: {type(e).__name__}",
        exc_info=True
    )
    raise