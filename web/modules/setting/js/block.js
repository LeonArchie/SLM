document.getElementById('blockButton').addEventListener('click', function () {
    const selectedUsers = Array.from(document.querySelectorAll('.userCheckbox:checked'))
        .map(cb => cb.dataset.userid);

    if (selectedUsers.length === 0) {
        showErrorMessage('Не выбран ни один пользователь для блокировки.');
        return;
    }

    if (!confirm('Вы уверены, что хотите заблокировать выбранных пользователей?')) {
        return;
    }

    fetch('/back/blockuser.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ user_ids: selectedUsers })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Пользователи успешно заблокированы!');
            location.reload();
        } else {
            showErrorMessage(data.message || 'Ошибка при блокировке пользователей.');
        }
    })
    .catch(error => {
        console.error('Ошибка при блокировке:', error);
        showErrorMessage('Произошла ошибка при блокировке пользователей.');
    });
});