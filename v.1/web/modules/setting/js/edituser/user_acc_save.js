// Ожидание полной загрузки DOM перед выполнением скрипта
document.addEventListener('DOMContentLoaded', function () {

    // Получение ссылки на кнопку "Сохранить" по её ID
    const saveButton = document.getElementById('saveButton');
    
    // Если кнопка не найдена, завершаем выполнение функции
    if (!saveButton) {
        return;
    }

    // Добавление обработчика события нажатия на кнопку "Сохранить"
    saveButton.addEventListener('click', function (e) {

        // Предотвращение стандартного поведения кнопки (например, отправки формы)
        e.preventDefault();

        // Получение элементов для отображения загрузки и сообщений об ошибках
        const loadingOverlay = document.getElementById('loading');
        const errorMessageElement = document.getElementById('error_message');

        // Если элемент загрузки не найден, завершаем выполнение функции
        if (!loadingOverlay) {
            return;
        }

        // Отображение индикатора загрузки
        loadingOverlay.style.display = 'flex';

        // Получение CSRF-токена из скрытого поля формы
        const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';
        // Если CSRF-токен отсутствует, показываем сообщение об ошибке и скрываем индикатор загрузки
        if (!csrfToken) {
            showErrorMessage('error', 'Ошибка', 'Ошибка 0028: Обновите страницу и повторите попытку.', 5000);
            loadingOverlay.style.display = 'none';
            return;
        }

        // Сбор данных из формы в объект
        const data = {
            csrf_token: csrfToken, // CSRF-токен для защиты от атак
            userID: document.getElementById('userID')?.value || '', // ID пользователя
            login: document.getElementById('login')?.value || '', // Логин
            lastName: document.getElementById('lastName')?.value || '', // Фамилия
            firstName: document.getElementById('firstName')?.value || '', // Имя
            fullName: document.getElementById('fullName')?.value || '', // Полное имя
            email: document.getElementById('email')?.value || '', // Электронная почта
            phone: document.getElementById('phone')?.value || '', // Телефон
            telegramUsername: document.getElementById('telegramUsername')?.value || '', // Имя пользователя в Telegram
            telegramID: document.getElementById('telegramID')?.value || '', // ID Telegram
            apiKey: document.getElementById('apiKey')?.value || '' // API-ключ
        };

        // Отправка данных на сервер с помощью Fetch API
        fetch('back/edituser/save_user_data.php', {
            method: 'POST', // Метод HTTP-запроса
            headers: {
                'Content-Type': 'application/json; charset=utf-8', // Указание типа содержимого
            },
            body: JSON.stringify(data, null, null), // Преобразование данных в JSON
            credentials: 'include', // Включение куки в запрос (для аутентификации)
        })
        .then(response => {
            // Проверка успешности ответа сервера
            if (!response.ok) {
                throw new Error(`Ошибка HTTP ${response.status}`);
            }
            // Преобразование ответа в JSON
            return response.json();
        })
        .then(result => {
            // Скрытие индикатора загрузки после получения ответа
            loadingOverlay.style.display = 'none';
            // Обработка результата
            if (result.success) {
                // Если успешно, показываем сообщение об успешном обновлении
                showErrorMessage('success', 'Успех', 'Данные успешно обновлены.', 3000);
            } else {
                // Если произошла ошибка, показываем сообщение об ошибке
                showErrorMessage('error', 'Ошибка', result.message || 'Ошибка 0029: Произошла неизвестная ошибка.', 5000);
            }
        })
        .catch(error => {
            // Обработка ошибок сети или других ошибок
            loadingOverlay.style.display = 'none';
            showErrorMessage('error', 'Ошибка', 'Ошибка 0030: Ошибка сервера.', 5000);
        });
    });
});