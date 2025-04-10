from flask import Flask, request, jsonify
from services.logger_service import LoggerService
from services.db_service import DatabaseService
from services.config_service import get_config
from routes.modules_routes import modules_bp
from routes.frod_route import frod_bp
from routes.verify import verify_bp
from routes.refresh import refresh_bp
from routes.addressbook_route import addressbook_bp
from flask_cors import CORS
import os
import sys

# Initialize minimal logger first
logger = LoggerService.get_logger('app.init')

def configure_services(config: dict):
    """Initialize all required services"""
    try:
        # Database initialization
        logger.info("Initializing database connection...")
        DatabaseService.initialize(config['db'])
        
        # Test database connection
        try:
            with DatabaseService.get_connection() as conn:
                with conn.cursor() as cur:
                    cur.execute("SELECT 1")
                    logger.info("Database connection test successful")
        except Exception as e:
            logger.critical(f"Database connection test failed: {str(e)}", exc_info=True)
            raise
            
        logger.info("All services initialized successfully")
        return True
        
    except Exception as e:
        logger.critical(f"Service initialization failed: {str(e)}", exc_info=True)
        return False

def create_app():
    """Application factory function"""
    app = Flask(__name__)
    CORS(app)  # Разрешает все origins
    
    try:
        # Load configuration
        config = get_config()
        
        # Reconfigure logger with loaded config
        LoggerService.get_logger('app', config)
        logger.info("Application logger reconfigured")
        
        # Configure Flask
        app.config.update({
            'SECRET_KEY': config['flask']['SECRET_KEY'],
            'JWT_SECRET_KEY': config['flask']['JWT']['SECRET_KEY'],
            'JWT_ACCESS_TOKEN_EXPIRES': config['flask']['JWT']['ACCESS_EXPIRES'],
            'JWT_REFRESH_TOKEN_EXPIRES': config['flask']['JWT']['REFRESH_EXPIRES']
        })
        
        # Initialize services
        if not configure_services(config):
            raise RuntimeError("Failed to initialize services")
            
    except Exception as e:
        logger.critical(f"Application configuration failed: {str(e)}", exc_info=True)
        sys.exit(1)

    # Import and register blueprints AFTER configuration
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
        ('Addresbook',addressbook_bp)
    ]
        
    for name, bp in blueprints:
        try:
            app.register_blueprint(bp)
            logger.info(f"Registered blueprint: {name}")
        except Exception as e:
            logger.error(f"Failed to register {name} blueprint: {str(e)}")

    # Request logging middleware
    @app.before_request
    def log_request():
        logger.info(
            f"Incoming {request.method} {request.path} | "
            f"IP: {request.remote_addr} | "
            f"User-Agent: {request.user_agent}"
        )
        if request.method in ['POST', 'PUT', 'PATCH'] and request.content_length:
            logger.debug(f"Request body: {request.get_data(as_text=True)[:500]}...")

    @app.after_request
    def log_response(response):
        logger.info(
            f"Outgoing {request.method} {request.path} | "
            f"Status: {response.status_code} | "
            f"Content-Type: {response.content_type}"
        )
        return response

    # Error handlers
    @app.errorhandler(404)
    def handle_404(error):
        logger.warning(f"404 Not Found: {request.path}")
        return jsonify({"error": "Not Found"}), 404

    @app.errorhandler(500)
    def handle_500(error):
        logger.error(f"500 Server Error: {str(error)}", exc_info=True)
        return jsonify({"error": "Internal Server Error"}), 500

    return app

# Create application instance
app = create_app()

if __name__ == '__main__':
    try:
        logger.info("Starting application...")
        app.run(host='0.0.0.0', port=5000, debug=False)
    except Exception as e:
        logger.critical(f"Application startup failed: {str(e)}", exc_info=True)
        sys.exit(1)