document.getElementById('deleteButton').addEventListener('click', function () {
    const selectedUsers = Array.from(document.querySelectorAll('.userCheckbox:checked'))
        .map(cb => cb.dataset.userid);

    if (selectedUsers.length === 0) {
        showErrorMessage('Не выбран ни один пользователь для удаления.');
        return;
    }

    if (!confirm('Вы уверены, что хотите удалить выбранных пользователей?')) {
        return;
    }

    fetch('/back/deluser.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ user_ids: selectedUsers })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Пользователи успешно удалены!');
            location.reload();
        } else {
            showErrorMessage(data.message || 'Ошибка при удалении пользователей.');
        }
    })
    .catch(error => {
        console.error('Ошибка при удалении:', error);
        showErrorMessage('Произошла ошибка при удалении пользователей.');
    });
});