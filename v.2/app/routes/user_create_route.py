from flask import Blueprint, request, jsonify
from services.logger_service import LoggerService
from services.db_service import DatabaseService
from services.user_create_service import UserCreateService

logger = LoggerService.get_logger('app.user.create.route')
user_create_bp = Blueprint('user_create', __name__)

@user_create_bp.route('/setting/user/create', methods=['POST'])
def create_user():
    """Endpoint for creating new users"""
    logger.info("Received user creation request")

    try:
        # Get data from request
        data = request.get_json()
        if not data:
            logger.warning("No data provided in request")
            return jsonify({"error": "No data provided"}), 400

        # Required fields check
        required_fields = ['access_token', 'user_id', 'userlogin', 'full_name', 'password_hash']
        if not all(field in data for field in required_fields):
            logger.warning("Missing required fields")
            return jsonify({"error": "Missing required fields"}), 400

        # Process user creation
        user_data = {
            'userlogin': data['userlogin'],
            'full_name': data['full_name'],
            'email': data.get('email', ''),
            'password_hash': data['password_hash']
        }

        result = UserCreateService.create_user(
            data['access_token'],
            data['user_id'],
            user_data
        )

        if 'error' in result:
            return jsonify(result[0]), result[1]

        # Save to database
        try:
            with DatabaseService.get_connection() as conn:
                with conn.cursor() as cur:
                    cur.execute(
                        """
                        INSERT INTO users 
                        (userid, userlogin, full_name, email, password_hash, active, add_ldap, regtimes)
                        VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
                        """,
                        (
                            result['userid'],
                            result['userlogin'],
                            result['full_name'],
                            result['email'],
                            result['password_hash'],
                            result['active'],
                            result['add_ldap'],
                            result['regtimes']
                        )
                    )
                    conn.commit()
                    logger.info(f"User {result['userid']} created successfully")
                    return jsonify({
                        "success": True,
                        "user_id": result['userid']
                    }), 201

        except Exception as e:
            logger.error(f"Database error during user creation: {str(e)}", exc_info=True)
            return jsonify({"error": "Database error"}), 500

    except Exception as e:
        logger.error(f"User creation error: {str(e)}", exc_info=True)
        return jsonify({"error": "Internal server error"}), 500