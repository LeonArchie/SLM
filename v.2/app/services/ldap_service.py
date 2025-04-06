import json
import os

def read_ldap_config():
    """Чтение конфигурации LDAP. При ошибках возвращает {'active': False}"""
    config_path = os.path.join(os.path.dirname(__file__), '..', 'config.json')
    try:
        with open(config_path, 'r') as config_file:
            config = json.load(config_file)
            return config.get('LDAP', {'active': False})  # Возвращаем LDAP-секцию или active=False
    except (FileNotFoundError, json.JSONDecodeError):
        return {'active': False}  # При ошибках чтения файла