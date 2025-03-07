document.getElementById('deleteButton').addEventListener('click', function () {
    // Получаем выбранных пользователей
    const selectedUsers = Array.from(document.querySelectorAll('.userCheckbox:checked'))
        .map(cb => cb.dataset.userid);

    //console.log("Выбранные пользователи:", selectedUsers); // Логирование выбранных пользователей

    // Проверка, что хотя бы один пользователь выбран
    if (selectedUsers.length === 0) {
        showErrorMessage('Не выбран ни один пользователь');
        return;
    }

    // Подтверждение действия
    if (!confirm('Вы уверены, что хотите удалить выбранных пользователей?')) {
        return;
    }

    // Получаем CSRF-токен и userid из скрытых полей или других источников
    const csrfToken = document.querySelector('input[name="csrf_token"]')?.value;
    const userId = document.querySelector('input[name="userid"]')?.value;

    // Проверка наличия всех необходимых параметров
    const missingParams = [];
    if (!csrfToken) missingParams.push('CSRF-токен');
    if (!userId) missingParams.push('ID текущего пользователя');
    if (selectedUsers.length === 0) missingParams.push('выбранные пользователи');

    if (missingParams.length > 0) {
        const errorMessage = 'Отсутствуют следующие параметры: ' + missingParams.join(', ');
        //console.error(errorMessage); // Логирование ошибки
        showErrorMessage(errorMessage);
        return;
    }

    //console.log("CSRF-токен:", csrfToken); // Логирование CSRF-токена
    //console.log("ID текущего пользователя:", userId); // Логирование userid

    // Отправка запроса на сервер
    fetch('back/all_account/deluser.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            user_ids: selectedUsers,
            csrf_token: csrfToken, // Передаем CSRF-токен
            userid: userId // Передаем userid
        })
    })
    .then(response => {
        //console.log("Ответ от сервера:", response); // Логирование ответа от сервера
        return response.json();
    })
    .then(data => {
        //console.log("Данные ответа:", data); // Логирование данных ответа
        if (data.success) {
            showErrorMessage('Выполнено.');
            location.reload();
        } else {
            showErrorMessage(data.message || 'Ошибка.');
        }
    })
    .catch(error => {
        //console.error('Ошибка при блокировке:', error); // Логирование ошибки
        showErrorMessage('Произошла ошибка при блокировке пользователей.');
    });
});