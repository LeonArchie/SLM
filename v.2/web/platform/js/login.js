document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('authForm');
    const loading = document.getElementById('loading');
    const authTypeSelect = document.getElementById('auth_type');
    // Автоматически определяем базовый URL
    const baseUrl = window.location.origin;

    // Проверяем, что все необходимые элементы существуют
    if (!form || !loading || !authTypeSelect) {
        console.error('Необходимые элементы формы не найдены');
        return;
    }

    let isFormSubmitting = false; // Флаг для защиты от повторной отправки

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

    const saveTokens = (data) => {
        try {
            localStorage.setItem('access_token', data.access_token);
            localStorage.setItem('refresh_token', data.refresh_token);
            document.cookie = `user_id=${encodeURIComponent(data.user_id)}; path=/; secure; samesite=strict`;
        } catch (error) {
            console.error('Ошибка при сохранении токенов:', error);
            showErrorMessage(
                'error',
                'Ошибка сохранения данных',
                'Невозможно сохранить токены. Попробуйте использовать другой браузер или отключить режим приватного просмотра.',
                10000
            );
            form.reset();
            setTimeout(() => window.location.reload(), 10000);
        }
    };

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (isFormSubmitting) return;
        isFormSubmitting = true;

        // Находим кнопку отправки формы
        const submitButton = form.querySelector('input[type="submit"]');
        if (!submitButton) {
            console.error('Кнопка отправки формы не найдена');
            isFormSubmitting = false;
            return;
        }

        submitButton.disabled = true; // Блокируем кнопку

        try {
            if (!validateForm()) {
                throw new Error('Форма заполнена некорректно');
            }

            loading.style.display = 'flex';

            const authType = authTypeSelect.value;
            const apiUrl = authType === 'internal'
                ? `${baseUrl}:5000/auth/login`
                : `${baseUrl}:5000/auth/ldap/login`;

            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    login: form.login.value.trim(),
                    password: form.password.value
                })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Неизвестная ошибка сервера');
            }

            if (!data.access_token || !data.refresh_token) {
                throw new Error('Некорректный ответ сервера');
            }

            saveTokens(data);
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
                submitButton.disabled = false; // Разблокируем кнопку
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