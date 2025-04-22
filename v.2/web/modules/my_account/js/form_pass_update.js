// Получаем элементы DOM
const changePasswordButton = document.getElementById('changePasswordButton');
const modalOverlay = document.getElementById('modalOverlay');
const passwdForm = document.getElementById('passwdForm');
const loadingOverlay = document.getElementById('loading');

// Константы API с уникальными именами
const PASSWORD_API_BASE_URL = `${window.location.protocol}//${window.location.hostname}:5000`;
const PASSWORD_API_ENDPOINT = `${PASSWORD_API_BASE_URL}/setting/user/pass-update`;

// Обработчик кнопки "Сменить пароль"
if (changePasswordButton) {
    changePasswordButton.addEventListener('click', showPasswordForm);
}

// Показать форму смены пароля
function showPasswordForm() {
    modalOverlay.style.display = 'flex';
}

// Скрыть форму смены пароля
function closePasswordForm() {
    modalOverlay.style.display = 'none';
    passwdForm.reset();
}

// Обработчик клика вне формы
modalOverlay.addEventListener('click', function(e) {
    if (e.target === modalOverlay) {
        closePasswordForm();
    }
});

// Обработчик отправки формы
if (passwdForm) {
    passwdForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Получаем значения полей
        const currentPassword = document.getElementById('current_password').value;
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        // Валидация паролей
        if (newPassword !== confirmPassword) {
            showErrorMessage('error', 'Ошибка', 'Новые пароли не совпадают.', 5000);
            return;
        }
        
        if (newPassword.length < 7) {
            showErrorMessage('error', 'Ошибка', 'Новый пароль должен содержать минимум 7 символов.', 5000);
            return;
        }
        
        if (newPassword === currentPassword) {
            showErrorMessage('error', 'Ошибка', 'Новый пароль должен отличаться от текущего.', 5000);
            return;
        }
        
        // Получаем токен и ID пользователя
        const token = localStorage.getItem('access_token');
        const userId = localStorage.getItem('user_id');
        
        if (!token || !userId) {
            showErrorMessage('error', 'Ошибка', 'Требуется авторизация.', 5000);
            return;
        }
        
        // Показываем индикатор загрузки
        loadingOverlay.style.display = 'flex';
        
        try {
            // Отправляем запрос на сервер
            const response = await fetch(PASSWORD_API_ENDPOINT, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    access_token: token,
                    user_id: userId,
                    old_pass: currentPassword,
                    new_pass_1: newPassword,
                    new_pass_2: confirmPassword
                })
            });
            
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.error || 'Неизвестная ошибка сервера');
            }
            
            // Успешное обновление пароля
            showErrorMessage('success', 'Успех', 'Пароль успешно изменён.', 3000);
            closePasswordForm();
            
        } catch (error) {
            console.error('Ошибка при смене пароля:', error);
            
            if (error.message.includes('expired')) {
                showErrorMessage('warning', 'Внимание', 'Сессия истекла. Требуется повторная авторизация.', 5000);
            } else if (error.message.includes('Old password is incorrect')) {
                showErrorMessage('error', 'Ошибка', 'Текущий пароль введён неверно.', 5000);
            } else {
                showErrorMessage('error', 'Ошибка', error.message || 'Не удалось изменить пароль.', 5000);
            }
            
        } finally {
            // Скрываем индикатор загрузки
            loadingOverlay.style.display = 'none';
        }
    });
}

// Обработчик кнопки "Отменить"
const cancelButton = passwdForm?.querySelector('.cancel');
if (cancelButton) {
    cancelButton.addEventListener('click', closePasswordForm);
}