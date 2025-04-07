import psycopg2
from psycopg2 import pool
from typing import Dict, Optional
from services.logger_service import LoggerService
from contextlib import contextmanager

logger = LoggerService.get_logger('app.db')

class DatabaseService:
    _connection_pool = None

    @classmethod
    def initialize(cls, db_config: Dict):
        """Initialize database connection pool"""
        logger.info(
            f"Initializing DB connection to {db_config['host']}:"
            f"{db_config['port']}/{db_config['name']}"
        )
        
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
            
            # Test connection
            with cls.get_connection() as conn:
                with conn.cursor() as cur:
                    cur.execute("SELECT version()")
                    db_version = cur.fetchone()
                    logger.debug(f"Database version: {db_version[0]}")
            
            logger.info("Database pool initialized successfully")
            
        except Exception as e:
            logger.critical(f"Database initialization failed: {str(e)}", exc_info=True)
            raise

    @classmethod
    @contextmanager
    def get_connection(cls):
        """Context manager for connection handling"""
        if cls._connection_pool is None:
            raise RuntimeError("Database connection pool not initialized")
        
        conn = cls._connection_pool.getconn()
        try:
            yield conn
        except Exception as e:
            conn.rollback()
            raise
        finally:
            cls._connection_pool.putconn(conn)

    @classmethod
    def get_user_by_login(cls, login: str) -> Optional[Dict]:
        """Retrieve user by login"""
        logger.debug(f"Searching user: {login}")
        
        try:
            with cls.get_connection() as conn:
                with conn.cursor() as cur:
                    cur.execute(
                        "SELECT userid, userlogin, password_hash, full_name, active "
                        "FROM users WHERE userlogin = %s",
                        (login,)
                    )
                    result = cur.fetchone()
                    
                    if result:
                        return {
                            'userid': result[0],
                            'userlogin': result[1],
                            'password_hash': result[2],
                            'full_name': result[3],
                            'active': bool(result[4])  # Ensure boolean
                        }
                    return None
                    
        except Exception as e:
            logger.error(f"User search failed: {str(e)}", exc_info=True)
            raise