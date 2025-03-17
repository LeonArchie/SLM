// Функция открытия формы
function openForm() {
    // Показываем модальное окно, устанавливая свойство display в 'flex'
    document.getElementById('modalOverlay').style.display = 'flex';
}

// Функция закрытия формы
function closeForm() {
    // Скрываем модальное окно, устанавливая свойство display в 'none'
    document.getElementById('modalOverlay').style.display = 'none';
    // Сбрасываем форму, чтобы очистить все поля ввода
    document.getElementById('passwdForm').reset();
}

// Обработчик отправки формы
document.getElementById('passwdForm').addEventListener('submit', function (e) {
    // Предотвращаем стандартное поведение формы (перезагрузку страницы)
    e.preventDefault();

    // Получаем значения из полей ввода
    const currentPassword = document.getElementById('current_password').value;
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;

    // Проверяем, совпадают ли новый пароль и его подтверждение
    if (newPassword !== confirmPassword) {
        // Если пароли не совпадают, показываем сообщение об ошибке
        showErrorMessage('error', 'Ошибка', 'Ошибка 0034: Новые пароли не совпадают!', 5000);
        return; // Прерываем выполнение функции
    }

    // Получаем CSRF-токен и admin_userid из скрытых полей формы
    const csrfToken = document.getElementsByName('csrf_token')[0]?.value;
    const adminUserid = document.getElementsByName('admin_userid')[0]?.value;

    // Получаем userid из скрытого поля формы
    const userid = document.getElementById('userID').value;

    // Проверяем, удалось ли получить все необходимые данные
    if (!csrfToken || !adminUserid || !userid) {
        // Если данные не получены, показываем сообщение об ошибке
        showErrorMessage('error', 'Ошибка', 'Ошибка 0035: Обновите страницу и повторите попытку.', 5000);
        return; // Прерываем выполнение функции
    }

    // Отправляем данные на сервер с помощью fetch
    fetch('back/edituser/update_user_pass.php', {
        method: 'POST', // Используем метод POST
        headers: {
            'Content-Type': 'application/json' // Указываем тип содержимого как JSON
        },
        body: JSON.stringify({ // Преобразуем данные в JSON-строку
            csrf_token: csrfToken,
            admin_userid: adminUserid,
            userid: userid,
            current_password: currentPassword,
            new_password: newPassword
        })
    })
    .then(response => response.json()) // Преобразуем ответ сервера в JSON
    .then(data => {
        // Обрабатываем ответ сервера
        if (data.success) {
            // Если пароль успешно изменен, показываем сообщение об успехе
            showErrorMessage('success', 'Успех', 'Пароль успешно изменен!', 3000);
            closeForm(); // Закрываем форму
        } else {
            // Если произошла ошибка, показываем сообщение об ошибке
            showErrorMessage('error', 'Ошибка', data.message || 'Ошибка 0036: Произошла неизвестная ошибка.', 5000);
        }
    })
    .catch(error => {
        // Если произошла ошибка при отправке данных, показываем сообщение об ошибке
        showErrorMessage('error', 'Ошибка', 'Ошибка 0037: Произошла неизвестная ошибка.', 5000);
    });
});

// Назначаем обработчик события на кнопку для открытия формы
document.getElementById('changePasswordButton').addEventListener('click', openForm);