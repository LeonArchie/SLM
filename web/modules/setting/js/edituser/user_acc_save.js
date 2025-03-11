document.addEventListener('DOMContentLoaded', function () {

    const saveButton = document.getElementById('saveButton');
    if (!saveButton) {
        return;
    }

    saveButton.addEventListener('click', function (e) {

        e.preventDefault();

        const loadingOverlay = document.getElementById('loading');
        const errorMessageElement = document.getElementById('error_message');

        if (!loadingOverlay) {
            return;
        }

        loadingOverlay.style.display = 'flex';

        const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';
        if (!csrfToken) {
            showErrorMessage('CSRF-токен не найден. Пожалуйста, обновите страницу.');
            loadingOverlay.style.display = 'none';
            return;
        }

        const data = {
            csrf_token: csrfToken,
            userID: document.getElementById('userID')?.value || '',
            login: document.getElementById('login')?.value || '',
            lastName: document.getElementById('lastName')?.value || '',
            firstName: document.getElementById('firstName')?.value || '',
            fullName: document.getElementById('fullName')?.value || '',
            email: document.getElementById('email')?.value || '',
            phone: document.getElementById('phone')?.value || '',
            telegramUsername: document.getElementById('telegramUsername')?.value || '',
            telegramID: document.getElementById('telegramID')?.value || '',
            apiKey: document.getElementById('apiKey')?.value || ''
        };

        fetch('back/edituser/save_user_data.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json; charset=utf-8', 
            },
            body: JSON.stringify(data, null, null), 
            credentials: 'include',
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Ошибка HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(result => {
            loadingOverlay.style.display = 'none';
            if (result.success) {
                showErrorMessage('Данные успешно обновлены.');
            } else {
                showErrorMessage(result.message || 'Произошла ошибка при обработке данных.');
            }
        })
        .catch(error => {
            loadingOverlay.style.display = 'none';
            showErrorMessage('Не удалось подключиться к серверу. Попробуйте позже.');
        });
    });
});