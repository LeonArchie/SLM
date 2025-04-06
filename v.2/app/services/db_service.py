import psycopg2
from psycopg2 import pool
from contextlib import contextmanager
import logging

logger = logging.getLogger('app.db')

class DatabaseService:
    _connection_pool = None

    @classmethod
    def initialize(cls, db_config):
        try:
            cls._connection_pool = psycopg2.pool.ThreadedConnectionPool(
                minconn=1,
                maxconn=10,
                host=db_config['host'],
                port=db_config['port'],
                dbname=db_config['name'],
                user=db_config['user'],
                password=db_config['password']
            )
            logger.info("PostgreSQL connection pool initialized")
        except Exception as e:
            logger.critical(f"DB connection failed: {str(e)}")
            raise

    @classmethod
    @contextmanager
    def get_connection(cls):
        conn = cls._connection_pool.getconn()
        try:
            yield conn
        except Exception as e:
            logger.error(f"DB error: {str(e)}")
            conn.rollback()
            raise
        finally:
            cls._connection_pool.putconn(conn)

    @classmethod
    def authenticate(cls, login, password):
        with cls.get_connection() as conn:
            with conn.cursor() as cursor:
                cursor.execute(
                    "SELECT userid, password_hash FROM users WHERE userlogin = %s",
                    (login,)
                )
                user = cursor.fetchone()
                if user and cls.check_password(password, user[1]):
                    return {'id': user[0]}
        return None

    @staticmethod
    def check_password(input_pwd, stored_hash):
        # Замените на реальную проверку (bcrypt)
        return input_pwd == stored_hash  # Пример! Используйте bcrypt на практике