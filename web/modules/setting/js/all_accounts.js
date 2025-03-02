document.addEventListener("DOMContentLoaded", function () {
    // Получаем все кнопки
    const editButton = document.getElementById('editButton');
    const blockButton = document.getElementById('blockButton');
    const deleteButton = document.getElementById('deleteButton');

    // Начальное состояние кнопок
    editButton.disabled = true;
    blockButton.disabled = true;
    deleteButton.disabled = true;

    // Обработчик изменения состояния чекбоксов
    function updateButtonStates() {
        const selectedCheckboxes = Array.from(document.querySelectorAll('.userCheckbox:checked'));

        if (selectedCheckboxes.length === 1) {
            // Если выбран один пользователь, активируем все кнопки
            editButton.disabled = false;
            blockButton.disabled = false;
            deleteButton.disabled = false;
        } else if (selectedCheckboxes.length > 1) {
            // Если выбрано несколько пользователей, активируем только "Блокировать" и "Удалить"
            editButton.disabled = true;
            blockButton.disabled = false;
            deleteButton.disabled = false;
        } else {
            // Если ни один пользователь не выбран, отключаем все кнопки
            editButton.disabled = true;
            blockButton.disabled = true;
            deleteButton.disabled = true;
        }
    }

    // Добавляем обработчик события для всех чекбоксов пользователей
    document.querySelectorAll('.userCheckbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateButtonStates);
    });

    // Обработчик для кнопки "Выбрать все"
    document.getElementById('selectAll').addEventListener('change', function () {
        const checkboxes = document.querySelectorAll('.userCheckbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateButtonStates(); // Обновляем состояние кнопок после выбора/снятия всех чекбоксов
    });

    // Обработчик для строк таблицы
    document.querySelectorAll('tbody tr').forEach(row => {
        row.addEventListener('click', function (event) {
            // Игнорируем клики по чекбоксам, чтобы не было конфликтов
            if (event.target.type === 'checkbox') return;

            const checkbox = row.querySelector('.userCheckbox');
            checkbox.checked = !checkbox.checked; // Переключаем состояние чекбокса
            row.classList.toggle('selected', checkbox.checked); // Добавляем/убираем класс для подсветки
            updateButtonStates(); // Обновляем состояние кнопок
        });
    });

    // Обработчик для кнопки "Редактировать"
    editButton.addEventListener('click', function () {
        const selectedCheckbox = document.querySelector('.userCheckbox:checked');
        if (selectedCheckbox) {
            const userId = selectedCheckbox.dataset.userid;
            redirectToEditUser(userId); // Перенаправление на страницу редактирования
        }
    });

    // Обработчик для кнопки "Блокировать"
    blockButton.addEventListener('click', function () {
        const selectedCheckboxes = Array.from(document.querySelectorAll('.userCheckbox:checked'));
        const userIds = selectedCheckboxes.map(checkbox => checkbox.dataset.userid);

        if (userIds.length > 0) {
            blockUsers(userIds); // Блокировка выбранных пользователей
        }
    });

    // Обработчик для кнопки "Удалить"
    deleteButton.addEventListener('click', function () {
        const selectedCheckboxes = Array.from(document.querySelectorAll('.userCheckbox:checked'));
        const userIds = selectedCheckboxes.map(checkbox => checkbox.dataset.userid);

        if (userIds.length > 0) {
            deleteUserAccounts(userIds); // Удаление выбранных пользователей
        }
    });

    // Функция для перенаправления на страницу редактирования
    function redirectToEditUser(userid) {
        fetch('edituser.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `userid=${encodeURIComponent(userid)}`
        })
        .then(response => {
            if (response.ok) {
                window.location.href = 'edituser.php';
            } else {
                showErrorMessage('Ошибка при переходе на страницу редактирования.');
            }
        })
        .catch(error => {
            console.error('Ошибка:', error);
            showErrorMessage('Произошла ошибка при отправке запроса.');
        });
    }

    // Функция для блокировки пользователей
    function blockUsers(userIds) {
        if (confirm('Вы уверены, что хотите заблокировать выбранных пользователей?')) {
            fetch('block_users.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ user_ids: userIds })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showErrorMessage('Пользователи успешно заблокированы.');
                    location.reload(); // Обновляем страницу после успешной операции
                } else {
                    showErrorMessage('Ошибка при блокировке пользователей.');
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                showErrorMessage('Произошла ошибка при выполнении операции.');
            });
        }
    }

    // Функция для удаления пользователей
    function deleteUserAccounts(userIds) {
        if (confirm('Вы уверены, что хотите удалить выбранных пользователей? Это действие нельзя отменить.')) {
            fetch('delete_users.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ user_ids: userIds })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showErrorMessage('Пользователи успешно удалены.');
                    location.reload(); // Обновляем страницу после успешной операции
                } else {
                    showErrorMessage('Ошибка при удалении пользователей.');
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                showErrorMessage('Произошла ошибка при выполнении операции.');
            });
        }
    }
});