from typing import Dict, Optional
from services.logger_service import LoggerService
from services.auth_service import AuthService
from services.token_service import TokenService
from services.db_service import DatabaseService
import time

logger = LoggerService.get_logger('app.user.pass.update')

class UserPassUpdateService:
    @staticmethod
    def update_password(
        access_token: str,
        user_id: str,
        old_password: str,
        new_password_1: str,
        new_password_2: str
    ) -> Dict:
        """Update user password after validating all requirements"""
        logger.info(f"Starting password update for user_id={user_id}")
        start_time = time.time()

        try:
            # Verify the access token first
            try:
                token_payload = TokenService.verify_token(access_token)
                if token_payload['user_id'] != user_id:
                    logger.warning(f"Token user_id mismatch: {token_payload['user_id']} != {user_id}")
                    return {
                        'success': False,
                        'error': 'Token does not match user_id',
                        'status_code': 401
                    }
            except Exception as e:
                logger.warning(f"Token verification failed: {str(e)}")
                return {
                    'success': False,
                    'error': 'Invalid or expired token',
                    'status_code': 401
                }

            # Get user from database
            with DatabaseService.get_connection() as conn:
                with conn.cursor() as cur:
                    cur.execute(
                        "SELECT password_hash FROM users WHERE userid = %s",
                        (user_id,)
                    )
                    result = cur.fetchone()
                    if not result:
                        logger.warning(f"User not found: {user_id}")
                        return {
                            'success': False,
                            'error': 'User not found',
                            'status_code': 404
                        }

                    stored_hash = result[0]

            # Verify old password
            if not AuthService.verify_password(old_password, stored_hash):
                logger.warning(f"Old password verification failed for user_id={user_id}")
                time.sleep(2)  # Small delay to prevent brute force
                return {
                    'success': False,
                    'error': 'Old password is incorrect',
                    'status_code': 401
                }

            # Validate new passwords
            if new_password_1 != new_password_2:
                logger.warning("New passwords don't match")
                return {
                    'success': False,
                    'error': 'New passwords do not match',
                    'status_code': 400
                }

            if len(new_password_1) < 7:
                logger.warning("New password is too short")
                return {
                    'success': False,
                    'error': 'New password must be at least 7 characters',
                    'status_code': 400
                }

            if AuthService.verify_password(new_password_1, stored_hash):
                logger.warning("New password cannot be same as old password")
                return {
                    'success': False,
                    'error': 'New password must be different from old password',
                    'status_code': 400
                }

            # Hash and store new password
            new_password_hash = AuthService.hash_password(new_password_1)

            with DatabaseService.get_connection() as conn:
                with conn.cursor() as cur:
                    cur.execute(
                        "UPDATE users SET password_hash = %s WHERE userid = %s",
                        (new_password_hash, user_id)
                    )
                    conn.commit()

            logger.info(
                f"Password updated successfully for user_id={user_id} "
                f"(took {time.time()-start_time:.2f} sec)"
            )
            return {
                'success': True,
                'message': 'Password updated successfully'
            }

        except Exception as e:
            logger.error(f"Password update error: {str(e)}", exc_info=True)
            return {
                'success': False,
                'error': 'Internal server error',
                'status_code': 500
            }