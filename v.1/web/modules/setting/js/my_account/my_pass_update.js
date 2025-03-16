// открытие формы
function openForm() {
    // Показываем модальное окно, устанавливая display в 'flex'
    document.getElementById('modalOverlay').style.display = 'flex';
}

// закрытие формы
function closeForm() {
    // Скрываем модальное окно, устанавливая display в 'none'
    document.getElementById('modalOverlay').style.display = 'none';
    // Сбрасываем форму, чтобы очистить все поля
    document.getElementById('passwdForm').reset();
}

// Функция для отображения сообщений
function showErrorMessage(type, title, message, duration = 3000) {
    // Создаем элемент для сообщения
    const messageElement = document.createElement('div');
    messageElement.className = `message ${type}`; // Добавляем класс для стилизации
    messageElement.innerHTML = `
        <strong>${title}</strong>
        <span>${message}</span>
    `;

    // Добавляем сообщение в контейнер для уведомлений
    const notificationContainer = document.getElementById('notificationContainer');
    if (!notificationContainer) {
        // Если контейнер для уведомлений не существует, создаем его
        const container = document.createElement('div');
        container.id = 'notificationContainer';
        document.body.appendChild(container);
    }
    document.getElementById('notificationContainer').appendChild(messageElement);

    // Устанавливаем таймер для автоматического удаления сообщения
    setTimeout(() => {
        messageElement.remove();
    }, duration);
}

// Обработчик формы
document.getElementById('passwdForm').addEventListener('submit', function (e) {
    // Предотвращаем стандартное поведение формы (перезагрузку страницы)
    e.preventDefault();

    // Получаем значения из полей формы
    const currentPassword = document.getElementById('current_password').value;
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;

    // Проверяем, совпадают ли новый пароль и его подтверждение
    if (newPassword !== confirmPassword) {
        // Если пароли не совпадают, показываем сообщение об ошибке
        showErrorMessage('error', 'Ошибка', 'Ошибка 0014: Новые пароли не совпадают!', 5000);
        return; // Прерываем выполнение функции
    }

    // Проверка CSRF-токена
    const csrfToken = document.getElementsByName('csrf_token')[0]?.value;
    if (!csrfToken) {
        // Если CSRF-токен не найден, показываем сообщение об ошибке
        showErrorMessage('error', 'Ошибка', 'Ошибка 0015: CSRF-токен не найден. Пожалуйста, обновите страницу.', 5000);
        return; // Прерываем выполнение функции
    }

    // Отправляем данные на сервер с помощью fetch
    fetch('back/my_account/update_pass.php', {
        method: 'POST', // Используем метод POST
        headers: {
            'Content-Type': 'application/json' // Указываем тип содержимого как JSON
        },
        body: JSON.stringify({ // Преобразуем данные в JSON-строку
            csrf_token: csrfToken, // Передаем CSRF-токен
            current_password: currentPassword, // Передаем текущий пароль
            new_password: newPassword // Передаем новый пароль
        })
    })
    .then(response => response.json()) // Преобразуем ответ сервера в JSON
    .then(data => {
        if (data.success) {
            // Если пароль успешно изменен, показываем сообщение и закрываем форму
            showErrorMessage('success', 'Успех', 'Пароль успешно изменен!', 3000);
            closeForm();
        } else {
            // Если произошла ошибка, показываем сообщение об ошибке
            showErrorMessage('error', 'Ошибка', data.message || 'Ошибка 0016: Ошибка сервера.', 5000);
        }
    })
    .catch(error => {
        // Если произошла ошибка при отправке данных, показываем сообщение об ошибке
        showErrorMessage('error', 'Ошибка', 'Ошибка 0017: Ошибка сервера.', 5000);
    });
});

// Кнопка для открытия формы
document.getElementById('changePasswordButton').addEventListener('click', openForm);