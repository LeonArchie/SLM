from services.db_service import DatabaseService
from services.privileges_service import PrivilegesService
from services.logger_service import LoggerService
from typing import List, Dict, Union

logger = LoggerService.get_logger('app.user_block.service')

class UserBlockService:
    REQUIRED_PRIVILEGE = "[SETTINGS] - Право блокировки учетной записи"

    @staticmethod
    def process_block_request(requesting_user_id: str, block_user_ids: Union[str, List[str]]) -> List[Dict]:
        """Process user block/unblock request after checking privileges"""
        logger.info(f"Processing block request from user {requesting_user_id} for users {block_user_ids}")
        
        # Convert single user_id to list for uniform processing
        if isinstance(block_user_ids, str):
            block_user_ids = [block_user_ids]
        
        try:
            # Check if requesting user has required privilege
            privileges = PrivilegesService.get_user_privileges(requesting_user_id)
            has_privilege = any(priv['name_privilege'] == UserBlockService.REQUIRED_PRIVILEGE for priv in privileges)
            
            if not has_privilege:
                logger.warning(f"User {requesting_user_id} lacks required privilege for blocking users")
                raise PermissionError("Insufficient privileges to block/unblock users")
            
            # Process each user
            results = []
            with DatabaseService.get_connection() as conn:
                with conn.cursor() as cur:
                    for user_id in block_user_ids:
                        try:
                            # Get current status
                            cur.execute(
                                "SELECT active FROM users WHERE userid = %s",
                                (user_id,)
                            )
                            result = cur.fetchone()
                            
                            if not result:
                                results.append({
                                    "user_id": user_id,
                                    "success": False,
                                    "message": "User not found"
                                })
                                continue
                            
                            current_status = result[0]
                            new_status = not current_status
                            
                            # Update status
                            cur.execute(
                                "UPDATE users SET active = %s WHERE userid = %s",
                                (new_status, user_id)
                            )
                            conn.commit()
                            
                            results.append({
                                "user_id": user_id,
                                "success": True,
                                "previous_status": current_status,
                                "new_status": new_status,
                                "message": "Status updated successfully"
                            })
                            
                            logger.info(f"User {user_id} status changed from {current_status} to {new_status}")
                            
                        except Exception as e:
                            conn.rollback()
                            logger.error(f"Failed to update user {user_id}: {str(e)}")
                            results.append({
                                "user_id": user_id,
                                "success": False,
                                "message": str(e)
                            })
            
            return results
            
        except PermissionError as e:
            raise
        except Exception as e:
            logger.error(f"Block request processing failed: {str(e)}", exc_info=True)
            raise