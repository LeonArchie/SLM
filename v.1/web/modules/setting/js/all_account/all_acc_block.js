document.getElementById('blockButton').addEventListener('click', function () {
    const selectedUsers = Array.from(document.querySelectorAll('.userCheckbox:checked'))
        .map(cb => cb.dataset.userid);

    if (selectedUsers.length === 0) {
        showErrorMessage('Не выбран ни один пользователь');
        return;
    }

    if (!confirm('Вы уверены, что хотите сменить статус выбранных пользователей?')) {
        return;
    }

    const csrfToken = document.querySelector('input[name="csrf_token"]')?.value;
    const userId = document.querySelector('input[name="userid"]')?.value;

    const missingParams = [];
    if (!csrfToken) missingParams.push('CSRF-токен');
    if (!userId) missingParams.push('ID текущего пользователя');
    if (selectedUsers.length === 0) missingParams.push('выбранные пользователи');

    if (missingParams.length > 0) {
        const errorMessage = 'Отсутствуют следующие параметры: ' + missingParams.join(', ');
        console.error(errorMessage);
        showErrorMessage(errorMessage);
        return;
    }

    fetch('back/all_account/blockuser.php', {
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