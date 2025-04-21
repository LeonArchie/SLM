# Импорт необходимых модулей Flask и сервисов
from flask import Blueprint, jsonify, request  # Компоненты для роутинга и обработки запросов
from services.ldap_active_service import read_ldap_config  # Сервис работы с LDAP конфигурацией
from services.logger_service import LoggerService  # Сервис логирования

# Инициализация логгера с указанием имени модуля
logger = LoggerService.get_logger('ldap_routes')

# Создание Blueprint для группировки LDAP-роутов
ldap_bp = Blueprint('ldap', __name__,)

# Декоратор для обработки GET-запросов по пути '/ldap/active/'
@ldap_bp.route('/ldap/active/', methods=['GET'])
def ldap_active():
    """Проверка активности LDAP сервиса"""
    
    # Логирование факта обращения к endpoint
    logger.debug(f"Запрос проверки активности LDAP от {request.remote_addr}")
    
    # Чтение конфигурации LDAP из сервиса
    config = read_ldap_config()
    
    # Обработка случая, когда в конфигурации содержится ошибка
    if 'error' in config:
        logger.error(f"Ошибка в конфигурации LDAP: {config['error']}")
        return jsonify({'active': False})  # Возвращаем статус неактивности
    
    # Получение статуса активности из конфигурации (по умолчанию False)
    is_active = config.get('active', False)
    
    # Логирование текущего статуса активности
    logger.info(f"Текущий статус активности LDAP: {'активен' if is_active else 'неактивен'}")
    
    # Возврат JSON-ответа со статусом активности
    return jsonify({'active': is_active})