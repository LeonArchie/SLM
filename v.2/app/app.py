from flask import Flask, request as flask_request  # Переименовываем импорт
from routes.ldap_routes import ldap_bp
from routes.version_routes import version_bp
from services.logger_service import setup_logger

app = Flask(__name__)
logger = setup_logger()

# Регистрируем blueprint
app.register_blueprint(ldap_bp)
app.register_blueprint(version_bp)

@app.before_request
def log_request():
    """Логирование входящих запросов"""
    logger.info(
        f"Request: {flask_request.method} {flask_request.path} "
        f"Headers: {dict(flask_request.headers)}"
    )

@app.after_request
def log_response(response):
    """Логирование исходящих ответов"""
    logger.info(
        f"Response: {response.status} "
        f"Headers: {dict(response.headers)}"
    )
    return response

if __name__ == '__main__':
    logger.info("Starting application")
    app.run(host='0.0.0.0', port=5000)