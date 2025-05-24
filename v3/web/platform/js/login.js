document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('authForm');
    const loading = document.getElementById('loading');
    const authTypeSelect = document.getElementById('auth_type');
    const baseUrl = window.location.origin;

    if (!form || !loading || !authTypeSelect) {
        console.error('Необходимые элементы формы не найдены');
        return;
    }

    let isFormSubmitting = false;

    const validateForm = () => {
        const login = form.login.value.trim();
        const password = form.password.value.trim();

        if (!login || !password) {
            showErrorMessage('error', 'Ошибка', 'Все поля обязательны для заполнения', 3000);
            return false;
        }

        if (password.length < 8) {
            showErrorMessage('error', 'Ошибка', 'Пароль должен содержать минимум 8 символов', 3000);
            return false;
        }

        return true;
    };

    const saveToSession = async (data) => {
        try {
            // Сохраняем все данные в localStorage
            localStorage.setItem('access_token', data.access_token);
            localStorage.setItem('refresh_token', data.refresh_token);
            localStorage.setItem('user_id', data.user_id);
            localStorage.setItem('user_name', data.user_name);
            const response = await fetch(`${baseUrl}/platform/back/save_session.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    access_token: data.access_token,
                    refresh_token: data.refresh_token,
                    user_id: data.user_id,
                    user_name: data.user_name
                })
            });

            if (!response.ok) {
                throw new Error('Ошибка сохранения сессии');
            }

            return await response.json();
        } catch (error) {
            console.error('Ошибка при сохранении в сессию:', error);
            throw error;
        }
    };

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (isFormSubmitting) return;
        isFormSubmitting = true;

        const submitButton = form.querySelector('input[type="submit"]');
        if (!submitButton) {
            console.error('Кнопка отправки формы не найдена');
            isFormSubmitting = false;
            return;
        }

        submitButton.disabled = true;

        try {
            if (!validateForm()) {
                throw new Error('Форма заполнена некорректно');
            }

            loading.style.display = 'flex';

            const authType = authTypeSelect.value;
            const apiUrl = authType === 'internal'
                ? `${baseUrl}:5000/auth/login`
                : `${baseUrl}:5000/auth/ldap/login`;

            // Шаг 1: Получаем токены от API
            const authResponse = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    login: form.login.value.trim(),
                    password: form.password.value
                })
            });

            const authData = await authResponse.json();

            if (!authResponse.ok) {
                throw new Error(authData.error || 'Неизвестная ошибка сервера');
            }

            if (!authData.access_token || !authData.refresh_token) {
                throw new Error('Некорректный ответ сервера');
            }

            // Шаг 2: Сохраняем данные в PHP сессию
            await saveToSession(authData);

            // Перенаправляем пользователя
            window.location.href = '/platform/dashboard.php';

        } catch (error) {
            console.error('Auth error:', error);
            showErrorMessage(
                'error',
                'Ошибка авторизации',
                error.message || 'Ошибка при подключении к серверу',
                5000
            );
        } finally {
            isFormSubmitting = false;
            if (submitButton) {
                submitButton.disabled = false;
            }
            loading.style.display = 'none';
        }
    });

    window.addEventListener('online', () => {
        document.querySelectorAll('.network-error').forEach(el => el.remove());
    });

    window.addEventListener('offline', () => {
        showErrorMessage('error', 'Ошибка', 'Отсутствует интернет-соединение', null);
    });
});