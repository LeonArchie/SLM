from flask import Blueprint, request, jsonify
from services.frod_service import check_privilege
from services.logger_service import logger

frod_bp = Blueprint('frod', __name__)

@frod_bp.route('/check-privilege', methods=['POST'])
def handle_check_privilege():
    try:
        data = request.get_json()
        if not data:
            return jsonify({"error": "No data provided"}), 400

        access_token = data.get('access_token')
        privileges_id = data.get('privileges_id')
        userid = data.get('userid')

        if not all([access_token, privileges_id, userid]):
            return jsonify({"error": "Missing required fields"}), 400
        
        result = check_privilege(access_token, privileges_id, userid)
        logger.info(f"Check result for {userid}: {result}")
        
        return jsonify({"has_privilege": result})

    except Exception as e:
        logger.error(f"API error: {str(e)}")
        return jsonify({"error": "Internal server error"}), 500