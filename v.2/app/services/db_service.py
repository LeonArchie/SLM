import psycopg2
from psycopg2 import pool
from typing import Dict, Optional
from services.logger_service import LoggerService
from contextlib import contextmanager

# Инициализация логгера для модуля работы с базой данных
logger = LoggerService.get_logger('app.db')

class DatabaseService:
    _connection_pool = None  # Пул соединений с базой данных

    @classmethod
    def initialize(cls, db_config: Dict):
        """Инициализация пула соединений с базой данных"""
        logger.info(
            f"Инициализация подключения к базе данных {db_config['host']}:"
            f"{db_config['port']}/{db_config['name']}"
        )
        
        try:
            # Создание пула соединений
            cls._connection_pool = psycopg2.pool.ThreadedConnectionPool(
                minconn=1,  # Минимальное количество соединений в пуле
                maxconn=100,  # Максимальное количество соединений в пуле
                host=db_config['host'],  # Хост базы данных
                port=db_config['port'],  # Порт базы данных
                dbname=db_config['name'],  # Имя базы данных
                user=db_config['user'],  # Пользователь базы данных
                password=db_config['password']  # Пароль пользователя
            )
            
            # Тестирование подключения к базе данных
            with cls.get_connection() as conn:
                with conn.cursor() as cur:
                    cur.execute("SELECT version()")
                    db_version = cur.fetchone()
                    logger.debug(f"Версия базы данных: {db_version[0]}")
            
            # Логирование успешной инициализации пула соединений
            logger.info("Пул соединений с базой данных успешно инициализирован")
            
        except Exception as e:
            # Логирование критической ошибки при инициализации пула
            logger.critical(f"Ошибка инициализации базы данных: {str(e)}", exc_info=True)
            raise

    @classmethod
    @contextmanager
    def get_connection(cls):
        """Менеджер контекста для управления соединениями с базой данных"""
        if cls._connection_pool is None:
            raise RuntimeError("Пул соединений с базой данных не инициализирован")
        
        conn = cls._connection_pool.getconn()  # Получение соединения из пула
        try:
            yield conn  # Предоставление соединения для использования
        except Exception as e:
            # Откат транзакции в случае ошибки
            conn.rollback()
            raise
        finally:
            # Возвращение соединения в пул
            cls._connection_pool.putconn(conn)

    @classmethod
    def get_user_by_login(cls, login: str) -> Optional[Dict]:
        """Получение пользователя по логину"""
        logger.debug(f"Поиск пользователя: {login}")
        
        try:
            # Использование менеджера контекста для получения соединения
            with cls.get_connection() as conn:
                with conn.cursor() as cur:
                    # Выполнение SQL-запроса для поиска пользователя
                    cur.execute(
                        "SELECT userid, userlogin, password_hash, full_name, active "
                        "FROM users WHERE userlogin = %s",
                        (login,)
                    )
                    result = cur.fetchone()
                    
                    if result:
                        # Формирование словаря с данными пользователя
                        return {
                            'userid': result[0],
                            'userlogin': result[1],
                            'password_hash': result[2],
                            'full_name': result[3],
                            'active': bool(result[4])  # Преобразование значения в булевый тип
                        }
                    return None  # Если пользователь не найден
                    
        except Exception as e:
            # Логирование ошибки при поиске пользователя
            logger.error(f"Ошибка поиска пользователя: {str(e)}", exc_info=True)
            raise