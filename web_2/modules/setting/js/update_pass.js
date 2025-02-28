// Функция открытия формы
function openForm() {
    document.getElementById('modalOverlay').style.display = 'flex';
}

// Функция закрытия формы
function closeForm() {
    document.getElementById('modalOverlay').style.display = 'none';
    document.getElementById('passwdForm').reset();
}

// Функция для показа сообщений об ошибках (только для пользователя)
function showErrorMessage(message) {
    alert(message); // Показываем ошибку через alert
}

// Обработчик отправки формы
document.getElementById('passwdForm').addEventListener('submit', function (e) {
    e.preventDefault();

    console.log('Клик по кнопке сохранения.');

    const currentPassword = document.getElementById('current_password').value;
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;

    if (newPassword !== confirmPassword) {
        showErrorMessage('Новые пароли не совпадают!');
        return;
    }

    // Проверка наличия CSRF-токена
    const csrfToken = document.getElementsByName('csrf_token')[0]?.value;
    if (!csrfToken) {
        showErrorMessage('CSRF-токен не найден. Пожалуйста, обновите страницу.');
        console.error('Ошибка: CSRF-токен не найден.'); // Отладочная информация в консоль
        return;
    }

    // Отправляем данные на сервер
    fetch('back/update_pass.php', {
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
            alert('Пароль успешно изменен!');
            closeForm();
        } else {
            showErrorMessage(data.message || 'Ошибка при изменении пароля.');
            console.error('Ошибка сервера:', data.message); // Отладочная информация в консоль
        }
    })
    .catch(error => {
        console.error('Ошибка при отправке данных:', error); // Отладочная информация в консоль
        showErrorMessage('Произошла ошибка при отправке данных.');
    });
});

// Кнопка для открытия формы
document.getElementById('changePasswordButton').addEventListener('click', openForm);