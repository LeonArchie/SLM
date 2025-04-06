import jwt
import datetime
from functools import wraps
from flask import request, jsonify, current_app

class TokenService:
    @staticmethod
    def generate_tokens(user_id):
        access_payload = {
            'user_id': user_id,
            'exp': datetime.datetime.utcnow() + datetime.timedelta(seconds=current_app.config['JWT_ACCESS_TOKEN_EXPIRES']),
            'type': 'access'
        }
        refresh_payload = {
            'user_id': user_id,
            'exp': datetime.datetime.utcnow() + datetime.timedelta(seconds=current_app.config['JWT_REFRESH_TOKEN_EXPIRES']),
            'type': 'refresh'
        }
        access_token = jwt.encode(access_payload, current_app.config['JWT_SECRET_KEY'], algorithm='HS256')
        refresh_token = jwt.encode(refresh_payload, current_app.config['JWT_SECRET_KEY'], algorithm='HS256')
        return access_token, refresh_token

    @staticmethod
    def verify_token(token):
        try:
            return jwt.decode(token, current_app.config['JWT_SECRET_KEY'], algorithms=['HS256'])
        except jwt.PyJWTError:
            return None

def token_required(f):
    @wraps(f)
    def decorated(*args, **kwargs):
        token = request.headers.get('Authorization', '').replace('Bearer ', '')
        if not token:
            return jsonify({"error": "Токен отсутствует"}), 401
        payload = TokenService.verify_token(token)
        if not payload or payload.get('type') != 'access':
            return jsonify({"error": "Невалидный токен"}), 401
        return f(payload['user_id'], *args, **kwargs)
    return decorated