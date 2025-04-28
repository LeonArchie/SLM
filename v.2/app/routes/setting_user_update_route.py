# setting_user_update_route.py
from flask import Blueprint, request, jsonify
from services.setting_user_update_service import UserUpdateService
from services.logger_service import LoggerService

logger = LoggerService.get_logger('app.user_update')

user_update_bp = Blueprint('user_update', __name__)

@user_update_bp.route('/setting/user/update', methods=['POST'])
def update_user():
    """Endpoint для обновления данных пользователя"""
    logger.info("Получен запрос на обновление данных пользователя")
    
    try:
        data = request.get_json()
        if not data:
            logger.warning("Данные не предоставлены в запросе")
            return jsonify({"error": "Данные запроса обязательны"}), 400

        # Проверка наличия обязательных полей
        required_fields = ['access_token', 'userid', 'full_name']
        missing_fields = [field for field in required_fields if field not in data]
        
        if missing_fields:
            logger.warning(f"Отсутствуют обязательные поля: {missing_fields}")
            return jsonify({
                "error": "Отсутствуют обязательные поля",
                "details": missing_fields
            }), 400

        result = UserUpdateService.process_update(data)
        
        if 'error' in result:
            logger.warning(f"Ошибка при обработке запроса: {result['error']}")
            return jsonify({
                "error": result['error'],
                "details": result.get('details')
            }), result.get('status_code', 400)
        
        logger.info(f"Данные пользователя успешно обновлены для userid={data['userid']}")
        return jsonify({"success": True, "message": "Данные успешно обновлены"}), 200

    except Exception as e:
        logger.error(f"Неожиданная ошибка: {str(e)}", exc_info=True)
        return jsonify({"error": "Внутренняя ошибка сервера"}), 500