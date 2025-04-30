document.addEventListener('DOMContentLoaded', function() {
    const blockUserBtn = document.getElementById('blockButton');
    
    if (blockUserBtn) {
        blockUserBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const blockUserId = document.getElementById('userID').value;
            
            if (!blockUserId) {
                showErrorMessage('error', 'Ошибка', 'Не удалось определить ID пользователя для блокировки.', 5000);
                return;
            }
            
            // Создаем модальное окно подтверждения
            const modal = document.createElement('div');
            modal.style.position = 'fixed';
            modal.style.top = '0';
            modal.style.left = '0';
            modal.style.width = '100%';
            modal.style.height = '100%';
            modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
            modal.style.display = 'flex';
            modal.style.justifyContent = 'center';
            modal.style.alignItems = 'center';
            modal.style.zIndex = '1000';
            
            modal.innerHTML = `
                <div style="background-color: white; padding: 20px; border-radius: 5px; max-width: 500px; width: 80%;">
                    <h3 style="margin-top: 0;">Подтверждение смены статуса</h3>
                    <p>Вы уверены, что хотите сменить статус этого пользователя?</p>
                    <div style="display: flex; justify-content: flex-end; margin-top: 20px; gap: 10px;">
                        <button id="confirmBlock" style="padding: 5px 15px; background-color: #dc3545; color: white; border: none; border-radius: 3px;">Сменить</button>
                        <button id="cancelBlock" style="padding: 5px 15px; background-color: #6c757d; color: white; border: none; border-radius: 3px;">Отмена</button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            document.getElementById('confirmBlock').addEventListener('click', function() {
                blockUser(blockUserId);
                document.body.removeChild(modal);
            });
            
            document.getElementById('cancelBlock').addEventListener('click', function() {
                document.body.removeChild(modal);
            });
        });
    }
    
    async function blockUser(blockUserId) {
        try {
            // Получаем данные из localStorage
            const accessToken = localStorage.getItem('access_token');
            const userId = localStorage.getItem('user_id');
            
            if (!accessToken || !userId) {
                throw new Error('Отсутствуют данные авторизации в localStorage');
            }
            
            const protocol = window.location.protocol;
            const host = window.location.host.replace(/:80|:443/g, '');
            const url = `${protocol}//${host}:5000/setting/user/block`;
            
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    access_token: accessToken,
                    user_id: userId,
                    block_user_id: blockUserId
                })
            });
            
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'Ошибка сервера');
            }
            
            if (data.success) {
                showErrorMessage('success', 'Успех', 'Стутас пользователя изменен.', 3000);
                setTimeout(() => window.location.reload(), 2000);
            } else {
                throw new Error(data.message || 'Не удалось изменить статус пользователя');
            }
        } catch (error) {
            console.error('Ошибка блокировки:', error);
            showErrorMessage(
                'error', 
                'Ошибка', 
                error.message || 'Не удалось выполнить запрос на изменение статуса', 
                5000
            );
        }
    }
});