import json
import os
from typing import Dict, Any

def get_config() -> Dict[str, Any]:
    """Чтение конфига с обработкой ошибок и аннотацией типов"""
    config_path = os.path.join(os.path.dirname(__file__), '..', 'config.json')
    default_config = {
        "LDAP": {"active": False},
        "LOG": {
            "app": "error.log",
            "max_bytes": 10485700,
            "backup_count": 5,
            "log_level": "DEBUG"
        }
    }
    
    try:
        with open(config_path, 'r') as f:
            config = json.load(f)
            # Мерджим с дефолтными значениями
            return {**default_config, **config}
    except Exception as e:
        print(f"Error reading config: {e}")
        return default_config