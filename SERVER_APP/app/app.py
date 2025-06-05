from flask import Flask, request, jsonify
from internal.config_service import config_service
from internal.logger_service import LoggerService
from internal.connect_bd import DatabaseService
from flask_cors import CORS
import os
import sys

# Инициализация логгера для начальной настройки приложения
logger = LoggerService.get_logger('app.init')

def create_app():
    """
    Фабричная функция для создания и настройки Flask-приложения.
    Выполняет:
    - Инициализацию конфигурации из config.json и .env
    - Настройку сервисов
    - Регистрацию всех blueprint'ов
    - Настройку middleware для логирования
    - Настройку обработчиков ошибок
    """
    app = Flask(__name__)
    CORS(app)  # Разрешаем CORS для всех доменов
    
    try:
        # 1. Загрузка обязательных параметров из .env
        logger.info("Загрузка обязательных параметров из .env...")
        flask_secret_key = config_service.get_env_var('FLASK_KEY')
        jwt_secret_key = config_service.get_env_var('JWT1_KEY')
        
        # 2. Загрузка только используемых конфигураций из config.json
        logger.info("Загрузка конфигурации из config.json...")
        app_config = config_service.get_file_config('app')
        jwt_config = config_service.get_file_config('jwt')
        log_config = config_service.get_file_config('log')
        version_config = config_service.get_file_config('version')

        # 3. Настройка логгера с загруженной конфигурацией
        LoggerService.get_logger('app', log_config)
        logger.info("Логгер приложения переинициализирован с детальными настройками")
        
        # 4. Настройка конфигурации Flask
        app.config.update({
            # Базовые настройки
            'SECRET_KEY': flask_secret_key,
            'JWT_SECRET_KEY': jwt_secret_key,
            
            # JWT настройки
            'JWT_ACCESS_TOKEN_EXPIRES': int(jwt_config.get('access_expires_second', 600)),
            'JWT_REFRESH_TOKEN_EXPIRES': int(jwt_config.get('refresh_expires_second', 1200)),
            
            # Настройки сервера
            'SERVER_CONFIG': {
                'HOST': app_config.get('host', '0.0.0.0'),
                'PORT': int(app_config.get('port', 5000)),
                'DEBUG': bool(log_config.get('debug', False)),
                'MAX_LOG_SIZE': int(app_config.get('max_log_size', 10240))  # 10KB по умолчанию
            },
            
            # Информация о версии
            'VERSION': version_config.get('current_version', '0.0.0')
        })

        # 5. Проверка подключения к базе данных
        logger.info("Проверка подключения к базе данных...")
        test_db_connection()
        
    except Exception as e:
        logger.critical(f"Ошибка инициализации приложения: {str(e)}", exc_info=True)
        sys.exit(1)

    # Регистрация всех компонентов приложения
    register_components(app)
    
    logger.info(f"Приложение успешно инициализировано. Версия: {app.config['VERSION']}")
    return app

def test_db_connection():
    """Проверяет подключение к базе данных и логирует результат"""
    try:
        with DatabaseService.get_connection() as conn:
            with conn.cursor() as cur:
                cur.execute("SELECT 1")
                logger.info("Подключение к базе данных: УСПЕШНО")
    except Exception as e:
        logger.critical(f"Ошибка подключения к базе данных: {str(e)}", exc_info=True)
        raise RuntimeError("Не удалось подключиться к базе данных")

def register_components(app):
    """
    Регистрирует все компоненты приложения:
    - Blueprints
    - Middleware
    - Обработчики ошибок
    """
    register_blueprints(app)
    setup_logging_middleware(app)
    setup_error_handlers(app)

def register_blueprints(app):
    """Регистрирует все blueprint'ы приложения"""
    # Импорт всех blueprint'ов
    from routes.ldap_active_routes import ldap_bp
    from routes.version_routes import version_bp
    from routes.auth_login_routes import auth_bp
    from routes.setting_user_modules_routes import modules_bp
    from routes.privileges_check_privilege_route import frod_bp
    from routes.auth_verify import verify_bp
    from routes.auth_refresh_route import refresh_bp
    from routes.addressbook_list_route import addressbook_bp
    from routes.setting_user_data_route import user_data_bp
    from routes.privileges_user_view_route import privileges_bp
    from routes.setting_user_update_route import user_update_bp
    from routes.setting_user_pass_update_route import user_pass_update_bp
    from routes.setting_user_list_route import user_list_bp
    from routes.setting_user_block_route import user_block_bp
    from routes.setting_user_create_route import user_create_bp
    from routes.privileges_get_all_route import privileges_get_all_bp
    from routes.privileges_scripts_get_all_route import privileges_scripts_get_all_bp
    from routes.privileges_scripts_user_view_route import privileges_scripts_user_view_bp
    from routes.setting_user_active_route import user_active_bp
    from routes.setting_user_full_data_route import user_full_data_bp
    from routes.setting_user_full_update_route import user_full_update_bp
    from routes.setting_user_admin_pass_update_route import admin_pass_update_bp

    # Список всех blueprint'ов для регистрации
    blueprints = [
        ('LDAP', ldap_bp),
        ('Version', version_bp),
        ('Auth', auth_bp),
        ('Verify', verify_bp),
        ('Refresh', refresh_bp),
        ('FROD', frod_bp),
        ('Modules', modules_bp),
        ('Addressbook', addressbook_bp),
        ('User Data', user_data_bp),
        ('Privileges', privileges_bp),
        ('User Update', user_update_bp),
        ('User Password Update', user_pass_update_bp),
        ('User List', user_list_bp),
        ('User Block', user_block_bp),
        ('User Create', user_create_bp),
        ('Privileges Get All', privileges_get_all_bp),
        ('Privileges Scripts Get All', privileges_scripts_get_all_bp),
        ('User Scripts View', privileges_scripts_user_view_bp),
        ('User Active', user_active_bp),
        ('User Full Data', user_full_data_bp),
        ('User Full Update', user_full_update_bp),
        ('Admin Password Update', admin_pass_update_bp)
    ]

    # Регистрация каждого blueprint'а
    for name, bp in blueprints:
        try:
            app.register_blueprint(bp)
            logger.info(f"Успешно зарегистрирован blueprint: {name} (префикс: {bp.url_prefix or '/'})")
        except Exception as e:
            logger.error(f"Ошибка регистрации blueprint {name}: {str(e)}", exc_info=True)

def setup_logging_middleware(app):
    """Настраивает middleware для детального логирования запросов и ответов"""

    @app.before_request
    def log_request_info():
        """Логирует информацию о входящем запросе"""
        logger.info(
            f"Входящий запрос: {request.method} {request.path} | "
            f"IP: {request.remote_addr} | "
            f"User-Agent: {request.user_agent}"
        )
        
        # Логирование тела запроса для методов, изменяющих данные
        if request.method in ['POST', 'PUT', 'PATCH']:
            log_request_body(app.config['SERVER_CONFIG']['MAX_LOG_SIZE'])

    def log_request_body(max_size):
        """Логирует тело запроса с учетом максимального размера"""
        try:
            request_data = request.get_data(as_text=True)
            if not request_data:
                return
                
            if len(request_data) < max_size:
                logger.debug(f"Тело запроса:\n{request_data}")
            else:
                logger.debug(f"Тело запроса (первые {max_size} символов):\n{request_data[:max_size]}...")
        except Exception as e:
            logger.warning(f"Не удалось залогировать тело запроса: {str(e)}")

    @app.after_request
    def log_response_info(response):
        """Логирует информацию об исходящем ответе"""
        logger.info(
            f"Ответ на запрос: {request.method} {request.path} | "
            f"Статус: {response.status_code} | "
            f"Content-Type: {response.content_type}"
        )
        
        # Логирование тела ответа для ошибок или в режиме отладки
        if response.status_code >= 400 or app.config['SERVER_CONFIG']['DEBUG']:
            log_response_body(response, app.config['SERVER_CONFIG']['MAX_LOG_SIZE'])
            
        return response

    def log_response_body(response, max_size):
        """Логирует тело ответа с учетом максимального размера"""
        try:
            response_data = response.get_data(as_text=True)
            if not response_data:
                return
                
            if len(response_data) < max_size:
                logger.debug(f"Тело ответа:\n{response_data}")
            else:
                logger.debug(f"Тело ответа (первые {max_size} символов):\n{response_data[:max_size]}...")
        except Exception as e:
            logger.warning(f"Не удалось залогировать тело ответа: {str(e)}")

def setup_error_handlers(app):
    """Настраивает обработчики ошибок в едином формате"""

    @app.errorhandler(400)
    def handle_bad_request(error):
        """Обработчик ошибок 400 (Bad Request)"""
        logger.warning(
            f"400 Bad Request: {request.method} {request.path} | "
            f"Ошибка: {str(error)} | "
            f"Параметры: {request.args}"
        )
        return jsonify({
            "success": False,
            "code": 400,
            "message": f"Некорректный запрос: {str(error)}"
        }), 400

    @app.errorhandler(401)
    def handle_unauthorized(error):
        """Обработчик ошибок 401 (Unauthorized)"""
        logger.warning(
            f"401 Unauthorized: {request.method} {request.path} | "
            f"Ошибка: {str(error)}"
        )
        return jsonify({
            "success": False,
            "code": 401,
            "message": "Требуется аутентификация"
        }), 401

    @app.errorhandler(403)
    def handle_forbidden(error):
        """Обработчик ошибок 403 (Forbidden)"""
        logger.warning(
            f"403 Forbidden: {request.method} {request.path} | "
            f"Ошибка: {str(error)}"
        )
        return jsonify({
            "success": False,
            "code": 403,
            "message": "Доступ запрещен"
        }), 403

    @app.errorhandler(404)
    def handle_not_found(error):
        """Обработчик ошибок 404 (Not Found)"""
        logger.warning(
            f"404 Not Found: {request.method} {request.path} | "
            f"Заголовки: {dict(request.headers)}"
        )
        return jsonify({
            "success": False,
            "code": 404,
            "message": f"Ресурс не найден: {request.path}"
        }), 404

    @app.errorhandler(405)
    def handle_method_not_allowed(error):
        """Обработчик ошибок 405 (Method Not Allowed)"""
        logger.warning(
            f"405 Method Not Allowed: {request.method} {request.path} | "
            f"Ошибка: {str(error)}"
        )
        return jsonify({
            "success": False,
            "code": 405,
            "message": "Метод не разрешен"
        }), 405

    @app.errorhandler(500)
    def handle_server_error(error):
        """Обработчик ошибок 500 (Internal Server Error)"""
        logger.error(
            f"500 Internal Server Error: {request.method} {request.path} | "
            f"Ошибка: {str(error)}",
            exc_info=True
        )
        return jsonify({
            "success": False,
            "code": 500,
            "message": "Внутренняя ошибка сервера"
        }), 500

    @app.errorhandler(Exception)
    def handle_unexpected_error(error):
        """Обработчик всех непредвиденных ошибок"""
        logger.critical(
            f"Необработанная ошибка: {request.method} {request.path} | "
            f"Ошибка: {str(error)}",
            exc_info=True
        )
        return jsonify({
            "success": False,
            "code": 500,
            "message": "Непредвиденная ошибка сервера"
        }), 500

# Создание экземпляра приложения
app = create_app()

if __name__ == '__main__':
    try:
        server_config = app.config['SERVER_CONFIG']
        logger.info(
            f"Запуск приложения версии {app.config['VERSION']} | "
            f"Адрес: {server_config['HOST']}:{server_config['PORT']} | "
            f"Режим отладки: {'ВКЛ' if server_config['DEBUG'] else 'ВЫКЛ'} | "
            f"Макс. размер лога: {server_config['MAX_LOG_SIZE']} байт"
        )
        
        app.run(
            host=server_config['HOST'],
            port=server_config['PORT'],
            debug=server_config['DEBUG'],
            threaded=True,
            use_reloader=server_config['DEBUG']
        )
    except Exception as e:
        logger.critical(f"Ошибка запуска приложения: {str(e)}", exc_info=True)
        sys.exit(1)