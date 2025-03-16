// открытие формы
function openForm() {
    document.getElementById('modalOverlay').style.display = 'flex';
}

//  закрытие формы
function closeForm() {
    document.getElementById('modalOverlay').style.display = 'none';
    document.getElementById('passwdForm').reset();
}

// Обработчик формы
document.getElementById('passwdForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const currentPassword = document.getElementById('current_password').value;
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;

    if (newPassword !== confirmPassword) {
        showErrorMessage('Новые пароли не совпадают!');
        return;
    }

    // Проверка токена
    const csrfToken = document.getElementsByName('csrf_token')[0]?.value;
    if (!csrfToken) {
        showErrorMessage('CSRF-токен не найден. Пожалуйста, обновите страницу.');
        return;
    }

    // Отправляем
    fetch('back/my_account/update_pass.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            csrf_token: csrfToken,
            current_password: currentPassword,
            new_password: newPassword
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showErrorMessage('Пароль успешно изменен!');
            closeForm();
        } else {
            showErrorMessage(data.message || 'Ошибка при изменении пароля.');
        }
    })
    .catch(error => {
        showErrorMessage('Произошла ошибка при отправке данных.');
    });
});

// Кнопка для открытия формы
document.getElementById('changePasswordButton').addEventListener('click', openForm);