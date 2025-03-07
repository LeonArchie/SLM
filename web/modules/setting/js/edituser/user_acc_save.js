document.addEventListener('DOMContentLoaded', function () {
    //console.log('Скрипт загружен и DOM полностью готов.');

    const saveButton = document.getElementById('saveButton');
    if (!saveButton) {
        //console.error('Кнопка "saveButton" не найдена в DOM.');
        return;
    }

    saveButton.addEventListener('click', function (e) {
        //console.log('Клик по кнопке сохранения.');

        e.preventDefault();

        const loadingOverlay = document.getElementById('loading');
        const errorMessageElement = document.getElementById('error_message');

        if (!loadingOverlay) {
            //console.error('Элемент "loading" не найден в DOM.');
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

        //console.log('Собранные данные:', data);
        
        //console.log('Отправляемые данные:', JSON.stringify(data));

        fetch('back/edituser/save_user_data.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json; charset=utf-8', // явно указываем кодировку
            },
            body: JSON.stringify(data, null, null), // Без экранирования
            credentials: 'include', // Важно для передачи кук
        })
        .then(response => {
            //console.log('Получен ответ от сервера:', response);
            if (!response.ok) {
                throw new Error(`Ошибка HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(result => {
            //console.log('Данные успешно получены от сервера:', result);
            loadingOverlay.style.display = 'none';
            if (result.success) {
                showErrorMessage('Данные успешно обновлены.');
            } else {
                showErrorMessage(result.message || 'Произошла ошибка при обработке данных.');
            }
        })
        .catch(error => {
            //console.error('Произошла ошибка при выполнении запроса:', error);
            loadingOverlay.style.display = 'none';
            showErrorMessage('Не удалось подключиться к серверу. Попробуйте позже.');
        });
    });
});