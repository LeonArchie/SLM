// Функция открытия формы
function openForm() {
    document.getElementById('modalOverlay').style.display = 'flex';
}

// Функция закрытия формы
function closeForm() {
    document.getElementById('modalOverlay').style.display = 'none';
    document.getElementById('passwdForm').reset();
}

// Обработчик отправки формы
document.getElementById('passwdForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const currentPassword = document.getElementById('current_password').value;
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;

    if (newPassword !== confirmPassword) {
        showErrorMessage('Новые пароли не совпадают!');
        return;
    }

    // Получаем CSRF-токен и admin_userid
    const csrfToken = document.getElementsByName('csrf_token')[0]?.value;
    const adminUserid = document.getElementsByName('admin_userid')[0]?.value;

    // Получаем userid
    const userid = document.getElementById('userID').value;

    if (!csrfToken || !adminUserid || !userid) {
        showErrorMessage('Не удалось получить необходимые данные. Пожалуйста, обновите страницу.');
        return;
    }

    fetch('back/edituser/update_user_pass.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            csrf_token: csrfToken,
            admin_userid: adminUserid,
            userid: userid,
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