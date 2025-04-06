# version_service.py
import json
import os
from typing import Dict, Any

def read_version_config() -> Dict[str, Any]:
    """Чтение конфигурации версии с обработкой ошибок"""
    config_path = os.path.join(os.path.dirname(__file__), '..', 'config.json')
    default_version = {'current_version': '0.0.0'}
    
    try:
        with open(config_path, 'r') as config_file:
            config = json.load(config_file)
            return config.get('version', default_version)
    except (FileNotFoundError, json.JSONDecodeError):
        return default_version