# Импорт необходимых модулей и сервисов
from flask import Blueprint, request, jsonify  # Flask-компоненты для роутинга и работы с запросами
from services.logger_service import LoggerService  # Сервис для логирования
from services.auth_login_service import AuthService  # Сервис аутентификации
from services.token_service import TokenService  # Сервис работы с токенами
from services.connect_db_service import DatabaseService  # Сервис работы с БД

# Инициализация логгера с указанием имени модуля
logger = LoggerService.get_logger('app.auth.routes')

# Создание Blueprint
auth_bp = Blueprint('auth', __name__)

# Декоратор, определяющий POST-метод для endpoint /auth/login
@auth_bp.route('/auth/login', methods=['POST'])
def login():
    """Маршрут входа в систему"""
    # Логирование факта обращения к endpoint
    logger.info(f"Запрос на /login от {request.remote_addr}")

    try:
        # Получение JSON-данных из тела запроса
        data = request.get_json()
        
        # Проверка наличия обязательных полей
        if not data or 'login' not in data or 'password' not in data:
            logger.warning("Невалидный запрос: отсутствует логин/пароль")
            # Возврат ошибки 400 (Bad Request) если нет логина/пароля
            return jsonify({"error": "Требуется логин и пароль"}), 400

        # Аутентификация пользователя через AuthService
        # Передаем логин, пароль и сервис для работы с БД
        user = AuthService.authenticate_user(
            data['login'],
            data['password'],
            DatabaseService
        )
        
        # Если пользователь не аутентифицирован
        if not user:
            logger.warning(f"Неудачная аутентификация для '{data['login']}'")
            # Возврат ошибки 401 (Unauthorized)
            return jsonify({"error": "Неверные учетные данные"}), 401

        # Генерация JWT-токенов (access и refresh) через TokenService
        tokens = TokenService.generate_tokens(user['id'])
        logger.info(f"Успешный вход пользователя {user['id']}")

        # Возврат успешного ответа с токенами и данными пользователя
        return jsonify({
            "access_token": tokens[0],  # Access token для авторизации
            "refresh_token": tokens[1],  # Refresh token для обновления access token
            "user_id": user['id'],  # ID пользователя
            "user_name": user['name']  # Имя пользователя
        })

    # Обработка любых непредвиденных ошибок
    except Exception as e:
        # Логирование ошибки с трассировкой стека (exc_info=True)
        logger.error(f"Ошибка обработки /login: {str(e)}", exc_info=True)
        # Возврат ошибки 500 (Internal Server Error)
        return jsonify({"error": "Внутренняя ошибка сервера"}), 500