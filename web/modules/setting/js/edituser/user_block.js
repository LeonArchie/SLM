document.getElementById('blockButton').addEventListener('click', function () {

    // Подтверждение действия
    if (!confirm('Вы уверены, что хотите сменить статус пользователя?')) {
        return;
    }

    // Получаем CSRF-токен и admin_userid
    const csrfToken = document.querySelector('input[name="csrf_token"]')?.value;
    const adminUserId = document.querySelector('input[name="admin_userid"]')?.value;

    // Получаем значение input с id="userID"
    const userIDInput = document.getElementById('userID');
    const userIDValue = userIDInput ? userIDInput.value : null;

    // Проверка наличия параметров
    const missingParams = [];
    if (!csrfToken) missingParams.push('CSRF-токен');
    if (!adminUserId) missingParams.push('ID текущего пользователя');
    if (!userIDValue) missingParams.push('ID пользователя');

    if (missingParams.length > 0) {
        const errorMessage = 'Отсутствуют следующие параметры: ' + missingParams.join(', ');
        showErrorMessage(errorMessage);
        return;
    }

    // Преобразуем userIDValue в массив
    const selectedUsers = [userIDValue];

    fetch('back/all_account/blockuser.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            user_ids: selectedUsers,
            csrf_token: csrfToken, 
            userid: adminUserId, 
        })
    })
    .then(response => {
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showErrorMessage('Выполнено.');
            location.reload();
        } else {
            showErrorMessage(data.message || 'Ошибка.');
        }
    })
    .catch(error => {
        showErrorMessage('Произошла ошибка при блокировке пользователей.');
    });
});