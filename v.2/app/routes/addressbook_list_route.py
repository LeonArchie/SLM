from flask import Blueprint, request, jsonify
from services.logger_service import LoggerService  # Сервис для логирования
from services.addressbook_list_service import AddressBookService

# Создание Blueprint для маршрута работы с адресной книгой
addressbook_bp = Blueprint('addressbook', __name__)

# Инициализация логгера для модуля маршрутов адресной книги
logger = LoggerService.get_logger('app.addressbook_route')

@addressbook_bp.route('/adresbook/list', methods=['POST']) 
def get_contact_list():
    """
    Получение списка контактов
    Требуемые параметры (JSON):
    - access_token: токен доступа пользователя
    - user_id: ID пользователя, запрашивающего список контактов
    """
    # Получение данных из тела запроса в формате JSON
    data = request.get_json()
    
    # Валидация входных данных
    if not data or 'access_token' not in data or 'user_id' not in data:
        # Логирование ошибки о недостатке обязательных параметров
        logger.warning(f"Некорректный запрос - отсутствуют обязательные поля: access_token или user_id")
        return jsonify({"error": "Требуются access_token и user_id"}), 400

    # Вызов сервиса для получения активных контактов
    result = AddressBookService.get_active_contacts(
        access_token=data['access_token'],  # Токен доступа
        requesting_user_id=data['user_id']  # ID пользователя, запрашивающего контакты
    )
    
    # Формирование ответа на основе результата сервиса
    if "error" in result:
        # Логирование ошибки, возвращенной сервисом
        logger.error(f"Ошибка при получении списка контактов: {result['error']}")
        return jsonify({"error": result["error"]}), result.get("status_code", 500)
    
    # Логирование успешного завершения операции
    logger.info(f"Список контактов успешно получен для user_id={data['user_id']}")
    return jsonify(result)