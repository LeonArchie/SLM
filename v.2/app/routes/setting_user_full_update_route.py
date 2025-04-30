from flask import Blueprint, request, jsonify
from services.setting_user_full_update_service import UserFullUpdateService
from services.logger_service import LoggerService

logger = LoggerService.get_logger('app.user_full_update')

user_full_update_bp = Blueprint('user_full_update', __name__)

@user_full_update_bp.route('/setting/user/full_update', methods=['POST'])
def full_update_user():
    """Endpoint для полного обновления данных пользователя администратором"""
    logger.info("Получен запрос на полное обновление данных пользователя")
    
    try:
        data = request.get_json()
        if not data:
            logger.warning("Данные не предоставлены в запросе")
            return jsonify({"error": "Данные запроса обязательны"}), 400

        # Проверка наличия обязательных полей
        required_fields = ['access_token', 'user_admin_id', 'user_update_id', 'user_data']
        missing_fields = [field for field in required_fields if field not in data]
        
        if missing_fields:
            logger.warning(f"Отсутствуют обязательные поля: {missing_fields}")
            return jsonify({
                "error": "Отсутствуют обязательные поля",
                "details": missing_fields
            }), 400

        result = UserFullUpdateService.process_full_update(data)
        
        if 'error' in result:
            logger.warning(f"Ошибка при обработке запроса: {result['error']}")
            return jsonify({
                "error": result['error'],
                "details": result.get('details'),
                "should_refresh": result.get('should_refresh', False)
            }), result.get('status_code', 400)
        
        logger.info(f"Данные пользователя {data['user_update_id']} успешно обновлены администратором {data['user_admin_id']}")
        return jsonify({"success": True, "message": "Данные успешно обновлены"}), 200

    except Exception as e:
        logger.error(f"Неожиданная ошибка: {str(e)}", exc_info=True)
        return jsonify({"error": "Внутренняя ошибка сервера"}), 500