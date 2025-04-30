document.addEventListener('DOMContentLoaded', function() {
    const changePasswordButton = document.getElementById('changePasswordButton');
    const modalOverlay = document.getElementById('modalOverlay');
    const userId = document.getElementById('userID').value;
    const adminId = localStorage.getItem('user_id');
    const accessToken = localStorage.getItem('access_token');

    // Константы API с уникальными именами
    const PASSWORD_API_BASE_URL = `${window.location.protocol}//${window.location.hostname}:5000`;
    const PASSWORD_API_ENDPOINT = `${PASSWORD_API_BASE_URL}/setting/user/admin-pass-update`;

    // Создаем модальное окно с формой
    function createPasswordResetModal() {
        return `
            <div class="passwd-form">
                <h3 style="margin-bottom: 20px; color: #4a5568; text-align: center;">
                    Сброс пароля пользователя
                </h3>
                <div id="adminPassStep" style="display: block;">
                    <div class="form-field">
                        <label for="adminPassword">Пароль администратора:</label>
                        <input type="password" id="adminPassword" placeholder="Введите ваш пароль" required>
                    </div>
                    <div style="text-align: center; margin-top: 20px;">
                        <button id="confirmReset" class="form-button" style="background-color: #3498db;">
                            Подтвердить сброс
                        </button>
                        <button id="cancelReset" class="form-button" style="background-color: #95a5a6;">
                            Отмена
                        </button>
                    </div>
                </div>
                <div id="confirmStep" style="display: none; text-align: center;">
                    <p style="margin-bottom: 20px;">Вы уверены, что хотите сбросить пароль пользователя ${userId}?</p>
                    <div>
                        <button id="finalConfirm" class="form-button" style="background-color: #3498db;">
                            Сбросить пароль
                        </button>
                        <button id="finalCancel" class="form-button" style="background-color: #95a5a6;">
                            Отмена
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    if (changePasswordButton) {
        changePasswordButton.addEventListener('click', function() {
            // Создаем и показываем модальное окно
            modalOverlay.innerHTML = createPasswordResetModal();
            modalOverlay.style.display = 'flex';

            // Обработчики для первой стадии (ввод пароля администратора)
            const confirmResetBtn = document.getElementById('confirmReset');
            const cancelResetBtn = document.getElementById('cancelReset');
            
            confirmResetBtn.addEventListener('click', function() {
                const adminPass = document.getElementById('adminPassword').value;
                if (!adminPass) {
                    showErrorMessage('warning', 'Внимание', 'Пожалуйста, введите пароль администратора', 3000);
                    return;
                }
                
                // Переходим к подтверждению
                document.getElementById('adminPassStep').style.display = 'none';
                document.getElementById('confirmStep').style.display = 'block';
            });

            cancelResetBtn.addEventListener('click', function() {
                modalOverlay.style.display = 'none';
            });

            // Обработчики для второй стадии (подтверждение сброса)
            const finalConfirmBtn = document.getElementById('finalConfirm');
            const finalCancelBtn = document.getElementById('finalCancel');
            
            finalConfirmBtn.addEventListener('click', function() {
                const adminPass = document.getElementById('adminPassword').value;
                
                // Показываем индикатор загрузки
                finalConfirmBtn.disabled = true;
                finalConfirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Обработка...';
                
                // Отправляем запрос на API
                fetch(PASSWORD_API_ENDPOINT, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${accessToken}`
                    },
                    body: JSON.stringify({
                        access_token: accessToken,
                        admin_id: adminId,
                        admin_pass: adminPass,
                        user_id: userId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showErrorMessage('success', 'Успех', 'Пароль успешно сброшен!', 3000);
                        if (data.new_password) {
                            // Если нужно показать новый пароль, можно добавить дополнительное сообщение
                        }
                        modalOverlay.style.display = 'none';
                    } else {
                        showErrorMessage('error', 'Ошибка', data.error || 'Не удалось сбросить пароль', 5000);
                        finalConfirmBtn.disabled = false;
                        finalConfirmBtn.innerHTML = 'Повторить попытку';
                    }
                })
                .catch(error => {
                    showErrorMessage('error', 'Ошибка сети', error.message || 'Не удалось выполнить запрос', 5000);
                    finalConfirmBtn.disabled = false;
                    finalConfirmBtn.innerHTML = 'Повторить попытку';
                });
            });

            finalCancelBtn.addEventListener('click', function() {
                modalOverlay.style.display = 'none';
            });
        });
    }
});