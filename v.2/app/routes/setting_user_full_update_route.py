from flask import Blueprint, request, jsonify
from services.logger_service import logger
from services.setting_user_full_update_service import update_user_data

# Создаем Blueprint для роутов обновления данных пользователей
user_full_update_bp = Blueprint('user_full_update', __name__)

@user_full_update_bp.route('/setting/user/full_update', methods=['POST'])
def handle_user_full_update():
    """
    Обработчик запроса на обновление данных пользователя
    """
    try:
        # Получаем данные из запроса
        data = request.get_json()
        
        # Проверяем наличие обязательных полей
        if not data or not all(key in data for key in ['access_token', 'user_admin_id', 'user_update_id', 'user_data']):
            logger.warning("Неполные данные в запросе")
            return jsonify({"status": False, "error": "Необходимы access_token, user_admin_id, user_update_id и user_data"}), 400
        
        # Извлекаем параметры
        access_token = data['access_token']
        admin_user_id = data['user_admin_id']
        update_user_id = data['user_update_id']
        user_data = data['user_data']
        
        # Обновляем данные пользователя
        result = update_user_data(access_token, admin_user_id, update_user_id, user_data)
        
        # Возвращаем результат
        if result.get('status'):
            return jsonify(result), 200
        else:
            return jsonify(result), 403 if result.get('error') == "Недостаточно прав" else 400
            
    except Exception as e:
        logger.error(f"Ошибка в API /setting/user/full_update: {str(e)}", exc_info=True)
        return jsonify({"status": False, "error": "Внутренняя ошибка сервера"}), 500