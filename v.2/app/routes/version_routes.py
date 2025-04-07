# version_routes.py
from flask import Blueprint, jsonify, request
from services.version_service import read_version_config
from services.logger_service import LoggerService

logger = LoggerService.get_logger('version_routes') 
version_bp = Blueprint('version', __name__, url_prefix='/version')

@version_bp.route('/', methods=['GET'])
def get_version():
    """Endpoint для получения текущей версии приложения"""
    logger.debug(f"Version check requested by {request.remote_addr}")
    version_config = read_version_config()
    
    if 'current_version' not in version_config:
        logger.error("Version information not found in config")
        return jsonify({'error': 'Version information not available'}), 500
    
    logger.info(f"Current version: {version_config['current_version']}")
    return jsonify({'version': version_config['current_version']})