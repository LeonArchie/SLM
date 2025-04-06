from flask import Flask, request
from routes.ldap_routes import ldap_bp
from routes.version_routes import version_bp
from routes.auth_routes import auth_bp
from services.db_service import DatabaseService
from services.logger_service import setup_logger
import json
import os

app = Flask(__name__)
logger = setup_logger()

# Загрузка конфигурации
with open('config.json') as f:
    config = json.load(f)
    app.config.update({
        'SECRET_KEY': config['flask']['SECRET_KEY'],
        'JWT_SECRET_KEY': config['flask']['JWT']['SECRET_KEY'],
        'JWT_ACCESS_TOKEN_EXPIRES': config['flask']['JWT']['ACCESS_EXPIRES'],
        'JWT_REFRESH_TOKEN_EXPIRES': config['flask']['JWT']['REFRESH_EXPIRES']
    })

# Инициализация БД
DatabaseService.initialize(config['db'])

# Регистрация Blueprints
app.register_blueprint(ldap_bp)
app.register_blueprint(version_bp)
app.register_blueprint(auth_bp)

@app.before_request
def log_request():
    logger.info(
        f"Request: {request.method} {request.path} | "
        f"IP: {request.remote_addr}"
    )

@app.after_request
def log_response(response):
    logger.info(f"Response: {response.status}")
    return response

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000)