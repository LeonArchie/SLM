from flask import Blueprint, request, jsonify
from services.addressbook_service import AddressBookService

addressbook_bp = Blueprint('addressbook', __name__)

@addressbook_bp.route('/adresbook/list', methods=['POST'])  # Обратите внимание на написание /adresbook/
def get_contact_list():
    """
    Получение списка контактов
    Требуемые параметры (JSON):
    - access_token
    - user_id
    """
    data = request.get_json()
    
    # Валидация
    if not data or 'access_token' not in data or 'user_id' not in data:
        return jsonify({"error": "access_token and user_id are required"}), 400

    # Вызов сервиса
    result = AddressBookService.get_active_contacts(
        access_token=data['access_token'],
        requesting_user_id=data['user_id']
    )
    
    # Формирование ответа
    if "error" in result:
        return jsonify({"error": result["error"]}), result.get("status_code", 500)
    return jsonify(result)