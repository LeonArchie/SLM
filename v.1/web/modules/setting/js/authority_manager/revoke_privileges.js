document.addEventListener("DOMContentLoaded", function () {
    const revokePrivilegesButton = document.getElementById('OffPrivileges');
    const revokePrivilegesForm = document.getElementById('revokePrivilegesForm');
    const selectElement = document.getElementById('privilegesToRevoke'); // Элемент выбора привилегий

    if (revokePrivilegesButton && revokePrivilegesForm && selectElement) {
        revokePrivilegesButton.addEventListener('click', function () {
            // Получаем выбранные UserID
            const selectedUserIDs = Array.from(document.querySelectorAll('.userCheckbox:checked')).map(checkbox => checkbox.dataset.userid);

            if (selectedUserIDs.length === 0) {
                showErrorMessage('warning', 'Внимание', 'Выберите хотя бы одного пользователя.', 7000);
                return;
            }

            // Записываем UserID в скрытое поле формы
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
                showErrorMessage('error', 'Ошибка', 'Ошибка CSRF-токена.', 10000);
                return;
            }

            // Получаем UserID и выбранные привилегии
            const userIDs = formData.get('userIDRevoke').split(',').map(id => id.trim());
            const privileges = Array.from(selectElement.selectedOptions).map(opt => opt.value);

            // Формируем данные для отправки
            const data = {
                csrf_token: csrfToken,
                userIDs: userIDs,
                privileges: privileges
            };

            // Отправляем данные на сервер
            fetch('back/authority_manager/revoke_privileges.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json;charset=UTF-8'
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error("Ошибка сети или сервера.");
                }
                return response.json();
            })
            .then(result => {
                if (result.success) {
                    showErrorMessage('success', 'Успех', 'Полномочия успешно сняты!', 5000);
                    closeForm('revokePrivilegesForm');
                    form.reset();
                } else {
                    showErrorMessage('error', 'Ошибка', result.message || 'Произошла ошибка при снятии полномочий.', 10000);
                }
            })
            .catch(error => {
                showErrorMessage('error', 'Ошибка', 'Произошла ошибка при отправке данных.', 10000);
            });
        });
    }
});

// Функции для открытия и закрытия формы
function openForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.style.display = 'block';
    }
}

function closeForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.style.display = 'none';
    }
}