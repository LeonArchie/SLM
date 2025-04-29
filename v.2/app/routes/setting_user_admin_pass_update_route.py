from flask import Blueprint, request, jsonify
from services.logger_service import LoggerService
from services.setting_user_admin_pass_update_service import UserAdminPassUpdateService

# Инициализация логгера
logger = LoggerService.get_logger('app.user.admin.pass.update.route')

# Создание Blueprint
admin_pass_update_bp = Blueprint('admin_pass_update', __name__)

@admin_pass_update_bp.route('/setting/user/admin-pass-update', methods=['POST'])
def admin_reset_password():
    """Endpoint для сброса пароля пользователя администратором"""
    logger.info("Получен запрос на сброс пароля администратором")

    try:
        data = request.get_json()
        if not data:
            logger.warning("Отсутствуют данные в запросе")
            return jsonify({"error": "Данные не предоставлены"}), 400

        required_fields = ['access_token', 'admin_id', 'admin_pass', 'user_id']
        if not all(field in data for field in required_fields):
            logger.warning("Отсутствуют обязательные поля")
            return jsonify({"error": "Не все обязательные поля предоставлены"}), 400

        result = UserAdminPassUpdateService.admin_reset_password(
            access_token=data['access_token'],
            admin_id=data['admin_id'],
            admin_pass=data['admin_pass'],
            user_id=data['user_id']
        )

        if not result['success']:
            return jsonify({"error": result['error']}), result.get('status_code', 400)

        # В реальном проекте не возвращаем пароль в ответе!
        response = {
            "success": True,
            "message": result['message']
            # "new_password": result['new_password']  # Только для отладки!
        }
        return jsonify(response), 200

    except Exception as e:
        logger.error(f"Ошибка в endpoint сброса пароля: {str(e)}", exc_info=True)
        return jsonify({"error": "Внутренняя ошибка сервера"}), 500