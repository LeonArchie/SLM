import logging
import os
from logging.handlers import RotatingFileHandler
from typing import Optional, Dict, Any

class LoggerService:
    _loggers = {}

    @classmethod
    def get_logger(cls, name: str = 'app', config: Optional[Dict[str, Any]] = None) -> logging.Logger:
        """Return configured logger instance with optional file handler"""
        if name in cls._loggers:
            return cls._loggers[name]

        logger = logging.getLogger(name)
        logger.setLevel(logging.DEBUG)

        # Create formatter
        formatter = logging.Formatter(
            '%(asctime)s | %(name)s | %(levelname)-8s | %(message)s '
            '[%(filename)s:%(lineno)d]'
        )

        # Console handler (always enabled)
        console_handler = logging.StreamHandler()
        console_handler.setFormatter(formatter)
        console_handler.setLevel(logging.DEBUG)
        logger.addHandler(console_handler)

        # File handler (if config provided)
        if config and 'LOG' in config:
            try:
                log_config = config['LOG']
                log_dir = os.path.dirname(log_config.get('app', 'app.log'))
                
                try:
                    if log_dir and not os.path.exists(log_dir):
                        os.makedirs(log_dir, exist_ok=True)
                except OSError as e:
                    logger.error(f"Failed to create log directory: {str(e)}")
                    raise

                try:
                    file_handler = RotatingFileHandler(
                        filename=log_config.get('app', 'app.log'),
                        maxBytes=log_config.get('max_bytes', 10*1024*1024),
                        backupCount=log_config.get('backup_count', 5),
                        encoding='utf-8'
                    )
                    file_handler.setFormatter(formatter)
                    
                    try:
                        file_handler.setLevel(getattr(logging, log_config.get('log_level', 'INFO').upper()))
                    except AttributeError:
                        file_handler.setLevel(logging.INFO)
                        logger.warning(f"Invalid log level in config, defaulting to INFO")
                    
                    logger.addHandler(file_handler)
                    logger.info(f"File logging configured: {log_config.get('app')}")

                except Exception as e:
                    logger.error(f"Failed to configure file handler: {str(e)}")
                    raise

            except Exception as e:
                logger.error(f"File logging configuration failed: {str(e)}")
                # Continue with console logging only

        cls._loggers[name] = logger
        return logger

# Global minimal logger instance
try:
    logger = LoggerService.get_logger('core')
except Exception as e:
    print(f"Critical: Failed to initialize logger: {str(e)}")
    raise