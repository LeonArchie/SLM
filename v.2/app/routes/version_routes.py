# version_routes.py
from flask import Blueprint, jsonify, request
from services.version_service import read_version_config
from services.logger_service import LoggerService

# Инициализация логгера для модуля получения версии приложения
logger = LoggerService.get_logger('version_routes') 

# Создание Blueprint для маршрута получения версии приложения
version_bp = Blueprint('version', __name__,)

@version_bp.route('/version/', methods=['GET'])
def get_version():
    """Endpoint для получения текущей версии приложения"""
    # Логирование запроса на проверку версии с указанием IP-адреса клиента
    logger.debug(f"Запрос на проверку версии от {request.remote_addr}")
    
    # Чтение конфигурации версии из сервиса
    version_config = read_version_config()
    
    # Проверка наличия информации о версии в конфигурации
    if 'current_version' not in version_config:
        # Логирование ошибки, если информация о версии отсутствует
        logger.error("Информация о версии не найдена в конфигурации")
        return jsonify({'error': 'Информация о версии недоступна'}), 500
    
    # Логирование текущей версии приложения
    logger.info(f"Текущая версия приложения: {version_config['current_version']}")
    
    # Возвращение информации о версии клиенту
    return jsonify({'version': version_config['current_version']})