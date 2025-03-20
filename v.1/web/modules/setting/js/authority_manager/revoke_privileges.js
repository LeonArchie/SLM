// Ожидаем полной загрузки DOM перед выполнением скрипта
document.addEventListener("DOMContentLoaded", function () {
    // Получаем элементы DOM: кнопку для снятия привилегий, форму и элемент выбора привилегий
    const revokePrivilegesButton = document.getElementById('OffPrivileges');
    const revokePrivilegesForm = document.getElementById('revokePrivilegesForm');
    const selectElement = document.getElementById('privilegesToRevoke'); // Элемент выбора привилегий

    // Проверяем, что все необходимые элементы существуют
    if (revokePrivilegesButton && revokePrivilegesForm && selectElement) {
        // Добавляем обработчик события нажатия на кнопку снятия привилегий
        revokePrivilegesButton.addEventListener('click', function () {
            // Получаем выбранные UserID из отмеченных чекбоксов
            const selectedUserIDs = Array.from(document.querySelectorAll('.userCheckbox:checked')).map(checkbox => checkbox.dataset.userid);

            // Если ни один пользователь не выбран, показываем сообщение об ошибке
            if (selectedUserIDs.length === 0) {
                showErrorMessage('warning', 'Внимание', 'Ошибка 0092: Выберите хотя бы одного пользователя.', 7000);
                return;
            }

            // Записываем выбранные UserID в скрытое поле формы
            document.getElementById('userIDRevoke').value = selectedUserIDs.join(', ');
            openForm('revokePrivilegesForm'); // Открываем форму
        });

        // Обработка отмены формы
        const cancelButton = document.getElementById('cancelRevokePrivilegesForm');
        if (cancelButton) {
            cancelButton.addEventListener('click', function () {
                closeForm('revokePrivilegesForm'); // Закрываем форму
                document.getElementById('revokePrivilegesFormContent').reset(); // Сбрасываем форму
            });
        }

        // Обработка выбора привилегий (множественный выбор)
        selectElement.addEventListener('mousedown', function (event) {
            const option = event.target;

            // Если кликнули на элемент OPTION, предотвращаем стандартное поведение и переключаем выбор
            if (option.tagName === 'OPTION') {
                event.preventDefault();
                option.selected = !option.selected; // Переключаем выбор
            }
        });

        // Обработка отправки формы
        document.getElementById('submitRevokePrivilegesForm').addEventListener('click', function () {
            const form = document.getElementById('revokePrivilegesFormContent');
            const formData = new FormData(form);

            // Проверка CSRF-токена
            const csrfToken = formData.get('csrf_token');
            if (!csrfToken) {
                console.error("Ошибка CSRF-токена: токен отсутствует.");
                showErrorMessage('error', 'Ошибка', 'Ошибка 0093: Обновите страницу и повторите попытку.', 10000);
                return;
            }

            // Получаем UserID и выбранные привилегии
            const userIDs = formData.get('userIDRevoke').split(',').map(id => id.trim());
            const privileges = Array.from(selectElement.selectedOptions).map(opt => opt.value);

            // Формируем данные для отправки на сервер
            const data = {
                csrf_token: csrfToken,
                userIDs: userIDs,
                privileges: privileges
            };

            // Отправляем данные на сервер с помощью fetch
            fetch('back/authority_manager/revoke_privileges.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json;charset=UTF-8'
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                // Проверяем, что ответ от сервера успешный
                if (!response.ok) {
                    throw new Error("Ошибка сети или сервера.");
                }
                return response.json();
            })
            .then(result => {
                // Обрабатываем результат ответа от сервера
                if (result.success) {
                    showErrorMessage('success', 'Успех', 'Полномочия успешно сняты!', 5000);
                    closeForm('revokePrivilegesForm');
                    form.reset();
                } else {
                    showErrorMessage('error', 'Ошибка', result.message || 'Ошибка 0094: Произошла неизвестная ошибка.', 10000);
                }
            })
            .catch(error => {
                // Обрабатываем ошибки при отправке данных
                showErrorMessage('error', 'Ошибка', 'Ошибка 0095: Произошла неизвестная ошибка.', 10000);
            });
        });
    }
});

// Функции для открытия и закрытия формы
function openForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.style.display = 'block'; // Показываем форму
    }
}

function closeForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.style.display = 'none'; // Скрываем форму
    }
}