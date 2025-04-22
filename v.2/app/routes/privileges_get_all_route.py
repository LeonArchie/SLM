from flask import Blueprint, request, jsonify
from services.privileges_get_all_services import PrivilegesGetAllService
from services.logger_service import LoggerService

# Initialize logger for privilege routes
logger = LoggerService.get_logger('app.privileges.get_all.routes')

# Create Blueprint for privilege routes
privileges_get_all_bp = Blueprint('privileges_get_all', __name__)

@privileges_get_all_bp.route('/privileges/get-all', methods=['POST'])
def get_all_privileges():
    """Endpoint to get all privileges"""
    logger.info("Received request to get all privileges")
    
    try:
        # Get data from request
        data = request.get_json()
        if not data or 'access_token' not in data or 'user_id' not in data:
            logger.warning("Invalid request data")
            return jsonify({"error": "access_token and user_id are required"}), 400

        # Verify token and user
        if not PrivilegesGetAllService.verify_token_and_user(data['access_token'], data['user_id']):
            logger.warning("Token verification failed or user_id mismatch")
            return jsonify({"error": "Invalid token or unauthorized"}), 401

        # Get all privileges
        privileges = PrivilegesGetAllService.get_all_privileges()
        
        logger.info(f"Successfully retrieved {len(privileges)} privileges")
        return jsonify({
            "status": "success",
            "privileges": privileges
        })
        
    except Exception as e:
        logger.error(f"Error in get_all_privileges: {str(e)}", exc_info=True)
        return jsonify({
            "error": "Internal server error",
            "details": str(e)
        }), 500