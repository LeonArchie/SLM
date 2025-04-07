import json
import os
from typing import Dict, Any
from services.logger_service import LoggerService

# Initialize basic logger without dependencies
logger = LoggerService.get_logger('app.config')

def get_config() -> Dict[str, Any]:
    """Load and validate application configuration"""
    default_config = {
        "LDAP": {"active": False},
        "LOG": {
            "app": "app.log",
            "max_bytes": 10485760,
            "backup_count": 5,
            "log_level": "INFO"
        },
        "db": {
            "host": "localhost",
            "port": 5432,
            "name": "default_db",
            "user": "default_user",
            "password": ""
        },
        "flask": {
            "SECRET_KEY": "default_secret_key",
            "JWT": {
                "SECRET_KEY": "default_jwt_secret",
                "ACCESS_EXPIRES": 3600,
                "REFRESH_EXPIRES": 86400
            }
        },
        "version": {
            "current_version": "0.0.0"
        }
    }

    try:
        config_path = os.path.join(os.path.dirname(__file__), '..', 'config.json')
        logger.debug(f"Loading config from: {config_path}")
        
        if not os.path.exists(config_path):
            logger.warning("Config file not found, using defaults")
            return default_config
            
        with open(config_path, 'r') as f:
            user_config = json.load(f)
            merged_config = {**default_config, **user_config}
            
            # Validate critical sections
            required_sections = ['flask', 'db']
            for section in required_sections:
                if section not in merged_config:
                    logger.error(f"Missing required config section: {section}")
                    raise ValueError(f"Missing config section: {section}")
            
            # Validate secret keys
            if (merged_config['flask']['SECRET_KEY'] == default_config['flask']['SECRET_KEY'] or
                merged_config['flask']['JWT']['SECRET_KEY'] == default_config['flask']['JWT']['SECRET_KEY']):
                logger.warning("Using default secret keys! This is insecure!")
            
            logger.info("Configuration loaded successfully")
            return merged_config
            
    except json.JSONDecodeError as e:
        logger.error(f"Config JSON decode error: {str(e)}")
        return default_config
    except Exception as e:
        logger.critical(f"Config loading failed: {str(e)}", exc_info=True)
        raise