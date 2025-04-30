from flask import Blueprint, request, jsonify
from services.logger_service import LoggerService
from services.setting_user_admin_pass_update_service import UserAdminPassUpdateService

# Инициализация логгера
logger = LoggerService.get_logger('app.user.admin.pass.update.route')

# Создание Blueprint
admin_pass_update_bp = Blueprint('admin_pass_update', __name__)

@admin_pass_update_bp.route('/setting/user/admin-pass-update', methods=['POST', 'OPTIONS'])
def admin_reset_password():
    """Endpoint для сброса пароля пользователя администратором"""
    
    # Обработка OPTIONS запроса для CORS
    if request.method == 'OPTIONS':
        response = jsonify({'success': True})
        response.headers.add('Access-Control-Allow-Origin', '*')
        response.headers.add('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        response.headers.add('Access-Control-Allow-Methods', 'POST, OPTIONS')
        return response

    # Логирование входящего POST запроса
    logger.info("Получен запрос на сброс пароля администратором")

    try:
        # Проверка Content-Type для POST запросов
        if not request.is_json:
            logger.warning("Неверный Content-Type: ожидается application/json")
            return jsonify({
                "success": False,
                "error": "Content-Type должен быть application/json"
            }), 415

        data = request.get_json()
        if not data:
            logger.warning("Отсутствуют данные в запросе")
            return jsonify({
                "success": False,
                "error": "Данные не предоставлены"
            }), 400

        # Проверка обязательных полей
        required_fields = ['access_token', 'admin_id', 'admin_pass', 'user_id']
        if not all(field in data for field in required_fields):
            missing = [field for field in required_fields if field not in data]
            logger.warning(f"Отсутствуют обязательные поля: {missing}")
            return jsonify({
                "success": False,
                "error": f"Не все обязательные поля предоставлены. Отсутствуют: {', '.join(missing)}"
            }), 400

        # Вызов сервиса для сброса пароля
        result = UserAdminPassUpdateService.admin_reset_password(
            access_token=data['access_token'],
            admin_id=data['admin_id'],
            admin_pass=data['admin_pass'],
            user_id=data['user_id']
        )

        if not result['success']:
            logger.warning(f"Ошибка сброса пароля: {result.get('error')}")
            return jsonify({
                "success": False,
                "error": result['error']
            }), result.get('status_code', 400)

        # Успешный ответ
        logger.info(f"Пароль успешно сброшен для пользователя {data['user_id']}")
        response = jsonify({
            "success": True,
            "message": result['message']
        })
        response.headers.add('Access-Control-Allow-Origin', '*')
        return response, 200

    except Exception as e:
        logger.error(f"Критическая ошибка в endpoint сброса пароля: {str(e)}", exc_info=True)
        return jsonify({
            "success": False,
            "error": "Внутренняя ошибка сервера"
        }), 500