from flask import Flask, request, jsonify
from services.logger_service import LoggerService
from services.guid_generate_service import GuidGenerateService
from services.db_service import DatabaseService
from services.config_service import get_config
from routes.modules_routes import modules_bp
from routes.frod_route import frod_bp
from routes.verify import verify_bp
from routes.refresh import refresh_bp
from routes.addressbook_route import addressbook_bp
from routes.user_data_route import user_data_bp
from routes.privileges_route import privileges_bp
from routes.user_update_route import user_update_bp
from routes.user_pass_update_route import user_pass_update_bp
from routes.user_list_route import user_list_bp
from routes.user_block_route import user_block_bp
from routes.user_create_route import user_create_bp
from flask_cors import CORS
import os
import sys

# Инициализация минимального логгера для начальной настройки приложения
logger = LoggerService.get_logger('app.init')

def configure_services(config: dict):
    """Инициализация всех необходимых сервисов"""
    try:
        # Инициализация подключения к базе данных
        logger.info("Инициализация подключения к базе данных...")
        DatabaseService.initialize(config['db'])
        
        # Тестирование подключения к базе данных
        try:
            with DatabaseService.get_connection() as conn:
                with conn.cursor() as cur:
                    cur.execute("SELECT 1")
                    logger.info("Тест подключения к базе данных успешно выполнен")
        except Exception as e:
            # Логирование критической ошибки тестирования подключения к базе данных
            logger.critical(f"Тест подключения к базе данных не удался: {str(e)}", exc_info=True)
            raise
            
        logger.info("Все сервисы успешно инициализированы")
        return True
        
    except Exception as e:
        # Логирование ошибки инициализации сервисов
        logger.critical(f"Ошибка инициализации сервисов: {str(e)}", exc_info=True)
        return False

def create_app():
    """Фабричная функция для создания экземпляра приложения"""
    app = Flask(__name__)
    CORS(app)  # Разрешение CORS для всех источников
    
    try:
        # Загрузка конфигурации
        config = get_config()
        
        # Переинициализация логгера с загруженной конфигурацией
        LoggerService.get_logger('app', config)
        logger.info("Логгер приложения переинициализирован")
        
        # Настройка Flask-приложения
        app.config.update({
            'SECRET_KEY': config['flask']['SECRET_KEY'],
            'JWT_SECRET_KEY': config['flask']['JWT']['SECRET_KEY'],
            'JWT_ACCESS_TOKEN_EXPIRES': config['flask']['JWT']['ACCESS_EXPIRES'],
            'JWT_REFRESH_TOKEN_EXPIRES': config['flask']['JWT']['REFRESH_EXPIRES']
        })
        
        # Инициализация сервисов
        if not configure_services(config):
            raise RuntimeError("Не удалось инициализировать сервисы")
            
    except Exception as e:
        # Логирование ошибки конфигурации приложения
        logger.critical(f"Ошибка конфигурации приложения: {str(e)}", exc_info=True)
        sys.exit(1)

    # Импорт и регистрация blueprint'ов ПОСЛЕ настройки
    from routes.ldap_routes import ldap_bp
    from routes.version_routes import version_bp
    from routes.auth_routes import auth_bp
    
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
        ('User Create', user_create_bp)
    ]
        
    for name, bp in blueprints:
        try:
            app.register_blueprint(bp)
            logger.info(f"Blueprint зарегистрирован: {name}")
        except Exception as e:
            logger.error(f"Не удалось зарегистрировать blueprint {name}: {str(e)}")

    # Middleware для логирования входящих запросов
    @app.before_request
    def log_request():
        logger.info(
            f"Входящий запрос {request.method} {request.path} | "
            f"IP: {request.remote_addr} | "
            f"User-Agent: {request.user_agent}"
        )
        if request.method in ['POST', 'PUT', 'PATCH'] and request.content_length:
            logger.debug(f"Тело запроса: {request.get_data(as_text=True)[:500]}...")

    # Middleware для логирования исходящих ответов
    @app.after_request
    def log_response(response):
        logger.info(
            f"Исходящий запрос {request.method} {request.path} | "
            f"Статус: {response.status_code} | "
            f"Content-Type: {response.content_type}"
        )
        return response

    # Обработчики ошибок
    @app.errorhandler(404)
    def handle_404(error):
        logger.warning(f"404 Not Found: {request.path}")
        return jsonify({"error": "Ресурс не найден"}), 404

    @app.errorhandler(500)
    def handle_500(error):
        logger.error(f"500 Server Error: {str(error)}", exc_info=True)
        return jsonify({"error": "Внутренняя ошибка сервера"}), 500

    return app

# Создание экземпляра приложения
app = create_app()

if __name__ == '__main__':
    try:
        logger.info("Запуск приложения...")
        app.run(host='0.0.0.0', port=5000, debug=False)
    except Exception as e:
        logger.critical(f"Ошибка запуска приложения: {str(e)}", exc_info=True)
        sys.exit(1)