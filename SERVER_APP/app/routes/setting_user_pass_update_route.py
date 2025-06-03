from flask import Blueprint, request, jsonify
from services.logger_service import LoggerService
from services.setting_user_pass_update_service import UserPassUpdateService

# Инициализация логгера для модуля обновления пароля пользователя
logger = LoggerService.get_logger('app.user.pass.update.route')

# Создание Blueprint для маршрута обновления пароля пользователя
user_pass_update_bp = Blueprint('user_pass_update', __name__)

@user_pass_update_bp.route('/setting/user/pass-update', methods=['POST'])
def update_password():
    """Endpoint для обновления пароля пользователя"""
    # Логирование начала обработки запроса на обновление пароля
    logger.info("Получен запрос на обновление пароля")

    try:
        # Получение данных из тела запроса в формате JSON
        data = request.get_json()
        required_fields = ['access_token', 'user_id', 'old_pass', 'new_pass_1', 'new_pass_2']
        
        # Проверка наличия всех обязательных полей в запросе
        if not data or any(field not in data for field in required_fields):
            # Логирование предупреждения о том, что запрос некорректен (отсутствуют обязательные поля)
            logger.warning("Некорректный запрос - отсутствуют обязательные поля")
            return jsonify({
                'error': 'Все поля обязательны: access_token, user_id, old_pass, new_pass_1, new_pass_2'
            }), 400

        # Вызов сервиса для обновления пароля
        result = UserPassUpdateService.update_password(
            access_token=data['access_token'],  # Токен доступа
            user_id=data['user_id'],           # ID пользователя
            old_password=data['old_pass'],     # Старый пароль
            new_password_1=data['new_pass_1'], # Новый пароль (первое введение)
            new_password_2=data['new_pass_2']  # Новый пароль (повторное введение)
        )

        # Если операция не выполнена успешно, возвращаем ошибку
        if not result['success']:
            return jsonify({'error': result['error']}), result.get('status_code', 400)

        # Логирование успешного обновления пароля
        logger.info(f"Пароль успешно обновлен для user_id={data['user_id']}")
        return jsonify({'message': result['message']}), 200

    except Exception as e:
        # Логирование общей ошибки при обработке запроса
        logger.error(f"Ошибка в endpoint обновления пароля: {str(e)}", exc_info=True)
        return jsonify({'error': 'Внутренняя ошибка сервера'}), 500