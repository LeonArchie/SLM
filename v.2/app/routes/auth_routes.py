from flask import Blueprint, request, jsonify
from services.token_service import TokenService, token_required
from services.db_service import DatabaseService

auth_bp = Blueprint('auth', __name__, url_prefix='/auth')

@auth_bp.route('/login', methods=['POST'])
def login():
    data = request.get_json()
    if not data or 'login' not in data or 'password' not in data:
        return jsonify({"error": "Требуется логин и пароль"}), 400

    user = DatabaseService.authenticate(data['login'], data['password'])
    if not user:
        return jsonify({"error": "Неверные учетные данные"}), 401

    access_token, refresh_token = TokenService.generate_tokens(user['id'])
    return jsonify({
        "access_token": access_token,
        "refresh_token": refresh_token,
        "user_id": user['id']
    })

@auth_bp.route('/refresh', methods=['POST'])
def refresh():
    refresh_token = request.json.get('refresh_token')
    if not refresh_token:
        return jsonify({"error": "Требуется refresh токен"}), 400

    payload = TokenService.verify_token(refresh_token)
    if not payload or payload.get('type') != 'refresh':
        return jsonify({"error": "Невалидный refresh токен"}), 401

    new_access_token, _ = TokenService.generate_tokens(payload['user_id'])
    return jsonify({"access_token": new_access_token})

@auth_bp.route('/test-auth')
@token_required
def test_auth(user_id):
    return jsonify({"message": f"Аутентификация успешна для user_id={user_id}"})