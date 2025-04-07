from flask import Blueprint, jsonify, request
from services.ldap_service import read_ldap_config
from services.logger_service import LoggerService

logger = LoggerService.get_logger('ldap_routes')
ldap_bp = Blueprint('ldap', __name__, url_prefix='/ldap')

@ldap_bp.route('/active/', methods=['GET'])
def ldap_active():
    logger.debug(f"LDAP active check requested by {request.remote_addr}")
    config = read_ldap_config()
    
    if 'error' in config:
        logger.error(f"Config error: {config['error']}")
        return jsonify({'active': False})
    
    is_active = config.get('active', False)
    logger.info(f"LDAP active status: {is_active}")
    return jsonify({'active': is_active})