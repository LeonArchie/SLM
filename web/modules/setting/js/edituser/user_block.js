document.getElementById('blockButton').addEventListener('click', function () {

    // Подтверждение действия
    if (!confirm('Вы уверены, что хотите сменить статус пользователя?')) {
        return;
    }

    // Получаем CSRF-токен и admin_userid из скрытых полей или других источников
    const csrfToken = document.querySelector('input[name="csrf_token"]')?.value;
    const adminUserId = document.querySelector('input[name="admin_userid"]')?.value;

    // Получаем значение из поля input с id="userID"
    const userIDInput = document.getElementById('userID');
    const userIDValue = userIDInput ? userIDInput.value : null;

    // Проверка наличия всех необходимых параметров
    const missingParams = [];
    if (!csrfToken) missingParams.push('CSRF-токен');
    if (!adminUserId) missingParams.push('ID текущего пользователя');
    if (!userIDValue) missingParams.push('ID пользователя');

    if (missingParams.length > 0) {
        const errorMessage = 'Отсутствуют следующие параметры: ' + missingParams.join(', ');
        //console.error(errorMessage); // Логирование ошибки
        showErrorMessage(errorMessage);
        return;
    }

    //console.log("CSRF-токен:", csrfToken); // Логирование CSRF-токена
    //console.log("ID текущего пользователя:", adminUserId); // Логирование adminUserId
    //console.log("ID пользователя:", userIDValue); // Логирование userID

    // Преобразуем userIDValue в массив
    const selectedUsers = [userIDValue]; // userIDValue передается как массив, даже если один элемент

    // Отправка запроса на сервер
    fetch('back/all_account/blockuser.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            user_ids: selectedUsers, // Передаем массив с ID пользователя
            csrf_token: csrfToken, // Передаем CSRF-токен
            userid: adminUserId, // Передаем admin_userid
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