// Находим кнопку с id "blockButton" и добавляем обработчик события "click"
document.getElementById('blockButton').addEventListener('click', function () {
    // Получаем список выбранных пользователей: находим все отмеченные чекбоксы с классом "userCheckbox"
    // и извлекаем из них значения data-userid (идентификаторы пользователей)
    const selectedUsers = Array.from(document.querySelectorAll('.userCheckbox:checked'))
        .map(cb => cb.dataset.userid);

    // Если ни один пользователь не выбран, показываем сообщение об ошибке и прерываем выполнение
    if (selectedUsers.length === 0) {
        showErrorMessage('error', 'Ошибка', 'Ошибка 0056: Не выбран ни один пользователь', 5000);
        return;
    }

    // Запрашиваем подтверждение у пользователя перед выполнением действия
    if (!confirm('Вы уверены, что хотите сменить статус выбранных пользователей?')) {
        return; // Если пользователь не подтвердил действие, прерываем выполнение
    }

    // Получаем CSRF-токен и ID текущего пользователя из скрытых полей формы
    const csrfToken = document.querySelector('input[name="csrf_token"]')?.value;
    const userId = document.querySelector('input[name="userid"]')?.value;

    // Проверяем наличие обязательных параметров
    const missingParams = [];
    if (!csrfToken) missingParams.push('CSRF-токен'); // Если отсутствует CSRF-токен
    if (!userId) missingParams.push('ID текущего пользователя'); // Если отсутствует ID пользователя
    if (selectedUsers.length === 0) missingParams.push('выбранные пользователи'); // Если отсутствуют выбранные пользователи

    // Если отсутствуют какие-либо параметры, выводим сообщение об ошибке и прерываем выполнение
    if (missingParams.length > 0) {
        const errorMessage = 'Ошибка 0057: Отсутствуют следующие параметры: ' + missingParams.join(', ');
        console.error(errorMessage); // Логируем ошибку в консоль
        showErrorMessage('error', 'Ошибка', errorMessage, 5000); // Показываем сообщение об ошибке пользователю
        return;
    }

    // Отправляем запрос на сервер для блокировки выбранных пользователей
    fetch('back/all_account/blockuser.php', {
        method: 'POST', // Используем метод POST
        headers: {
            'Content-Type': 'application/json' // Указываем тип содержимого как JSON
        },
        body: JSON.stringify({ // Преобразуем данные в JSON-формат
            user_ids: selectedUsers, // Идентификаторы выбранных пользователей
            csrf_token: csrfToken, // CSRF-токен для защиты от атак
            userid: userId // ID текущего пользователя
        })
    })
    .then(response => {
        return response.json(); // Преобразуем ответ сервера в JSON
    })
    .then(data => {
        // Обрабатываем ответ сервера
        if (data.success) {
            showErrorMessage('success', 'Успех', 'Выполнено.', 3000); // Показываем сообщение об успешном выполнении
            location.reload(); // Перезагружаем страницу для обновления данных
        } else {
            showErrorMessage('error', 'Ошибка', data.message || 'Ошибка 0058: Произошла неизвестная ошибка.', 5000); // Показываем сообщение об ошибке, если операция не удалась
        }
    })
    .catch(error => {
        // Обрабатываем ошибки, возникшие при выполнении запроса
        showErrorMessage('error', 'Ошибка', 'Ошибка 0059: Произошла неизвестная ошибка.', 5000);
    });
});