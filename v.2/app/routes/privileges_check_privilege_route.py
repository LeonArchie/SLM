# Импорт необходимых модулей Flask и сервисов
from flask import Blueprint, request, jsonify  # Компоненты для роутинга и обработки запросов
from services.privileges_check_privilege_service import check_privilege  # Сервис проверки привилегий
from services.logger_service import logger  # Логгер для записи событий

# Создание Blueprint для группировки роутов связанных с проверкой привилегий
# Префикс URL не указан, поэтому роуты будут доступны по корневому пути
frod_bp = Blueprint('frod', __name__)

# Декоратор для обработки POST-запросов по пути '/privileges/check-privilege'
@frod_bp.route('/privileges/check-privilege', methods=['POST'])
def handle_check_privilege():
    """Обработчик проверки привилегий пользователя"""
    try:
        # Получаем JSON-данные из тела запроса
        data = request.get_json()
        
        # Проверяем, что данные были переданы
        if not data:
            logger.warning("Получен пустой запрос")
            return jsonify({"error": "Не предоставлены данные"}), 400

        # Извлекаем необходимые параметры из запроса
        access_token = data.get('access_token')
        privileges_id = data.get('privileges_id')
        userid = data.get('userid')

        # Проверяем наличие всех обязательных полей
        if not all([access_token, privileges_id, userid]):
            logger.warning(f"Отсутствуют обязательные поля в запросе: {data}")
            return jsonify({"error": "Отсутствуют обязательные поля"}), 400
        
        # Вызываем сервис проверки привилегий
        result = check_privilege(access_token, privileges_id, userid)
        
        # Логируем результат проверки
        logger.info(f"Результат проверки для пользователя {userid}: {'есть' if result else 'нет'} привилегии")
        
        # Возвращаем результат в формате JSON
        return jsonify({"has_privilege": result})

    except Exception as e:
        # Обрабатываем любые непредвиденные ошибки
        logger.error(f"Ошибка в API: {str(e)}")
        return jsonify({"error": "Внутренняя ошибка сервера"}), 500