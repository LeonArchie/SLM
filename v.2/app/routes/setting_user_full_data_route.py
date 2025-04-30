from flask import Blueprint, request, jsonify
from services.logger_service import logger
from services.setting_user_full_data_service import get_user_full_data

# Создаем Blueprint для роутов управления пользователями
user_full_data_bp = Blueprint('user_full_data', __name__)

@user_full_data_bp.route('/setting/user/full_data', methods=['POST'])
def handle_user_full_data():
    """
    Обработчик запроса полных данных пользователя
    """
    try:
        # Получаем данные из запроса
        data = request.get_json()
        
        # Проверяем наличие обязательных полей
        if not data or not all(key in data for key in ['access_token', 'user_admin_id', 'user_check_id']):
            logger.warning("Неполные данные в запросе")
            return jsonify({"error": "Необходимы access_token, user_admin_id и user_check_id", "status": False}), 400
        
        # Извлекаем параметры
        access_token = data['access_token']
        admin_user_id = data['user_admin_id']
        check_user_id = data['user_check_id']
        
        # Получаем данные пользователя
        result = get_user_full_data(access_token, admin_user_id, check_user_id)
        
        # Возвращаем результат
        if result.get('status'):
            return jsonify(result), 200
        else:
            return jsonify(result), 403 if result.get('error') == "Недостаточно прав" else 404
            
    except Exception as e:
        logger.error(f"Ошибка в API /setting/user/full_data: {str(e)}", exc_info=True)
        return jsonify({"error": "Внутренняя ошибка сервера", "status": False}), 500