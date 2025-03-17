document.getElementById('deleteButton').addEventListener('click', function () {
    // Получаем список выбранных пользователей
    const selectedUsers = Array.from(document.querySelectorAll('.userCheckbox:checked'))
        .map(cb => cb.dataset.userid);

    // Если ни один пользователь не выбран, показываем сообщение об ошибке
    if (selectedUsers.length === 0) {
        showErrorMessage('warning', 'Внимание', 'Ошибка 0060: Не выбран ни один пользователь.', 3000);
        return;
    }

    // Подтверждение удаления
    if (!confirm('Вы уверены, что хотите удалить выбранных пользователей?')) {
        return;
    }

    // Получаем CSRF-токен и ID текущего пользователя
    const csrfToken = document.querySelector('input[name="csrf_token"]')?.value;
    const userId = document.querySelector('input[name="userid"]')?.value;

    // Проверяем наличие всех необходимых параметров
    const missingParams = [];
    if (!csrfToken) missingParams.push('CSRF-токен');
    if (!userId) missingParams.push('ID текущего пользователя');
    if (selectedUsers.length === 0) missingParams.push('выбранные пользователи');

    // Если отсутствуют параметры, показываем сообщение об ошибке
    if (missingParams.length > 0) {
        const errorMessage = 'Ошибка 0061: Отсутствуют следующие параметры: ' + missingParams.join(', ');
        showErrorMessage('error', 'Ошибка', errorMessage, 5000);
        return;
    }

    // Отправляем запрос на сервер
    fetch('back/all_account/deluser.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            user_ids: selectedUsers,
            csrf_token: csrfToken,
            userid: userId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Если удаление прошло успешно, показываем сообщение об успехе и перезагружаем страницу
            showErrorMessage('success', 'Успех', 'Пользователи успешно удалены.', 3000);
            setTimeout(() => location.reload(), 3000); // Перезагрузка страницы через 3 секунды
        } else {
            // Если произошла ошибка, показываем сообщение об ошибке
            showErrorMessage('error', 'Ошибка', data.message || 'Ошибка 0062: Произошла неизвестная ошибка.', 5000);
        }
    })
    .catch(error => {
        // Если произошла ошибка при выполнении запроса, показываем сообщение об ошибке
        showErrorMessage('error', 'Ошибка', 'Ошибка 0063: Произошла неизвестная ошибка.', 5000);
    });
});