// Функция для получения URL API
function getCreateUserApiUrl() {
    const protocol = window.location.protocol;
    const host = window.location.host.replace(/:80|:443/g, '');
    return `${protocol}//${host}:5000/setting/user/create`;
}

// Основная функция для создания пользователя
async function handleCreateUser(event) {
    event.preventDefault();
    
    // Проверяем JWT перед отправкой
    const isTokenValid = await verifyAndRefreshToken();
    if (!isTokenValid) {
        showErrorMessage('error', 'Ошибка авторизации', 'Пожалуйста, войдите снова.', 5000);
        return;
    }

    const form = document.getElementById('createUserForm');
    if (!form) return;

    const formData = new FormData(form);
    const userData = {
        access_token: localStorage.getItem('access_token'),
        user_id: localStorage.getItem('user_id'),
        csrf_token: formData.get('csrf_token'),
        userlogin: formData.get('userlogin'),
        full_name: formData.get('full_name'),
        password_hash: formData.get('password'),
        user_off_email: formData.get('email')
    };

    if (!validateUserData(userData)) {
        return;
    }

    try {
        const response = await fetch(getCreateUserApiUrl(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('access_token')}`
            },
            body: JSON.stringify(userData)
        });

        const data = await response.json();
        
        if (!response.ok) {
            const errorMsg = data.error || 'Ошибка сервера при создании пользователя';
            showErrorMessage('error', 'Ошибка', errorMsg, 5000);
            return;
        }

        showErrorMessage('success', 'Успех', 'Пользователь успешно создан!', 3000);
        form.reset();
        document.getElementById('createUserModal').style.display = 'none';
        
        // Обновляем страницу через 1.5 секунды после показа сообщения
        setTimeout(() => {
            location.reload();
        }, 1500);
        
    } catch (error) {
        console.error('Ошибка при создании пользователя:', error);
        showErrorMessage('error', 'Ошибка', `Не удалось создать пользователя: ${error.message}`, 5000);
    }
}

// Функция валидации данных с использованием showErrorMessage
function validateUserData(data) {
    if (!data.userlogin || !data.full_name || !data.password_hash || !data.user_off_email) {
        showErrorMessage('warning', 'Внимание', 'Все поля обязательны для заполнения', 3000);
        return false;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(data.user_off_email)) {
        showErrorMessage('warning', 'Внимание', 'Пожалуйста, введите корректный email', 3000);
        return false;
    }

    if (data.password_hash.length < 6) {
        showErrorMessage('warning', 'Внимание', 'Пароль должен содержать не менее 6 символов', 3000);
        return false;
    }

    return true;
}

// Инициализация после загрузки DOM
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('createUserForm');
    if (form) {
        form.addEventListener('submit', handleCreateUser);
    }
    
    const submitButton = document.querySelector('#createUserForm .submit-button');
    if (submitButton) {
        submitButton.addEventListener('click', handleCreateUser);
    }
});