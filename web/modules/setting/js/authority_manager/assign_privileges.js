document.addEventListener("DOMContentLoaded", function () {
    const assignPrivilegesButton = document.getElementById('AssignPrivileges');
    const assignPrivilegesForm = document.getElementById('assignPrivilegesForm');
    const selectElement = document.getElementById('privilegesToAssign');

    if (assignPrivilegesButton && assignPrivilegesForm && selectElement) {
        // Логирование открытия формы
        assignPrivilegesButton.addEventListener('click', function () {
            console.log("Открыта форма назначения полномочий.");

            // Получаем выбранные UserID из чекбоксов
            const selectedUserIDs = Array.from(document.querySelectorAll('.userCheckbox:checked')).map(checkbox => checkbox.dataset.userid);

            // Проверяем, что выбран хотя бы один пользователь
            if (selectedUserIDs.length === 0) {
                showErrorMessage('warning', 'Внимание', 'Выберите хотя бы одного пользователя.', 7000);
                console.warn("Пользователь не выбран.");
                return;
            }

            // Заполняем поле UserID в форме
            const userID = selectedUserIDs.join(', '); // Если нужно несколько UserID
            document.getElementById('userIDAssign').value = userID;

            // Открываем форму
            openForm('assignPrivilegesForm');
            console.log("Форма назначения полномочий открыта.");
        });

        // Логирование закрытия формы
        const cancelButton = document.getElementById('cancelAssignPrivilegesForm');
        if (cancelButton) {
            cancelButton.addEventListener('click', function () {
                console.log("Форма назначения полномочий закрыта.");
                closeForm('assignPrivilegesForm');
                document.getElementById('assignPrivilegesFormContent').reset(); // Очищаем форму
            });
        }

        // Обработка кликов на опциях
        selectElement.addEventListener('mousedown', function (event) {
            const option = event.target;

            // Если кликнули на option
            if (option.tagName === 'OPTION') {
                // Отменяем стандартное поведение
                event.preventDefault();

                // Переключаем состояние выбора
                option.selected = !option.selected;

                // Логируем выбранные значения
                const selectedValues = Array.from(selectElement.selectedOptions).map(opt => opt.value);
                console.log("Выбранные привилегии:", selectedValues);
            }
        });

        // Отправка формы
        document.getElementById('submitAssignPrivilegesForm').addEventListener('click', function () {
            const form = document.getElementById('assignPrivilegesFormContent');
            const formData = new FormData(form);

            // Валидация CSRF-токена
            const csrfToken = formData.get('csrf_token');
            if (!csrfToken) {
                console.error("Ошибка CSRF-токена: токен отсутствует.");
                showErrorMessage('error', 'Ошибка', 'Ошибка CSRF-токена.', 10000);
                return;
            }

            // Подготовка данных для отправки
            const userIDs = formData.get('userIDAssign').split(',').map(id => id.trim());
            const privileges = Array.from(selectElement.selectedOptions).map(opt => opt.value);

            const data = {
                csrf_token: csrfToken,
                userIDs: userIDs,
                privileges: privileges
            };

            console.log("Отправка данных на сервер:", data);

            // Отправка данных на сервер
            fetch('back/authority_manager/AssignPrivileges.php', {
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
                    console.log("Полномочия успешно назначены:", result);
                    showErrorMessage('success', 'Успех', 'Полномочия успешно назначены!', 5000);
                    closeForm('assignPrivilegesForm');
                    form.reset();
                } else {
                    console.error("Ошибка при назначении полномочий:", result.message);
                    showErrorMessage('error', 'Ошибка', result.message || 'Произошла ошибка при назначении полномочий.', 10000);
                }
            })
            .catch(error => {
                console.error('Ошибка при отправке данных:', error.message);
                showErrorMessage('error', 'Ошибка', 'Произошла ошибка при отправке данных.', 10000);
            });
        });
    }
});

// Функция для открытия формы
function openForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.style.display = 'block'; // Показываем форму
        console.log(`Форма ${formId} открыта.`);
    } else {
        console.error("Форма не найдена:", formId);
    }
}

// Функция для закрытия формы
function closeForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.style.display = 'none'; // Скрываем форму
        console.log(`Форма ${formId} закрыта.`);
    } else {
        console.error("Форма не найдена:", formId);
    }
}