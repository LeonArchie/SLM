// Ожидаем, пока весь HTML-документ будет загружен и готов к взаимодействию
document.addEventListener("DOMContentLoaded", function () {
    // Получаем ссылки на элементы DOM: кнопку, форму и выпадающий список
    const assignPrivilegesButton = document.getElementById('AssignPrivileges');
    const assignPrivilegesForm = document.getElementById('assignPrivilegesForm');
    const selectElement = document.getElementById('privilegesToAssign');

    // Проверяем, что все необходимые элементы существуют на странице
    if (assignPrivilegesButton && assignPrivilegesForm && selectElement) {
        // Добавляем обработчик события нажатия на кнопку "Назначить привилегии"
        assignPrivilegesButton.addEventListener('click', function () {

            // Получаем ID выбранных пользователей (через отмеченные чекбоксы)
            const selectedUserIDs = Array.from(document.querySelectorAll('.userCheckbox:checked')).map(checkbox => checkbox.dataset.userid);

            // Если ни один пользователь не выбран, показываем сообщение об ошибке и прерываем выполнение
            if (selectedUserIDs.length === 0) {
                showErrorMessage('warning', 'Внимание', 'Ошибка 0100: Выберите хотя бы одного пользователя.', 7000);
                return;
            }

            // Объединяем ID пользователей в строку и записываем в скрытое поле формы
            const userID = selectedUserIDs.join(', ');
            document.getElementById('userIDAssign').value = userID;

            // Открываем форму для назначения привилегий
            openForm('assignPrivilegesForm');
        });

        // Получаем кнопку "Отмена" в форме и добавляем обработчик события
        const cancelButton = document.getElementById('cancelAssignPrivilegesForm');
        if (cancelButton) {
            cancelButton.addEventListener('click', function () {
                // Закрываем форму и сбрасываем её содержимое
                closeForm('assignPrivilegesForm');
                document.getElementById('assignPrivilegesFormContent').reset();
            });
        }

        // Добавляем обработчик события на выпадающий список для множественного выбора
        selectElement.addEventListener('mousedown', function (event) {
            const option = event.target;

            // Если кликнули на элемент списка (OPTION), предотвращаем стандартное поведение
            if (option.tagName === 'OPTION') {
                event.preventDefault();

                // Переключаем состояние выбора элемента
                option.selected = !option.selected;

                // Получаем значения всех выбранных элементов
                const selectedValues = Array.from(selectElement.selectedOptions).map(opt => opt.value);
            }
        });

        // Добавляем обработчик события на кнопку отправки формы
        document.getElementById('submitAssignPrivilegesForm').addEventListener('click', function () {
            // Получаем форму и её данные
            const form = document.getElementById('assignPrivilegesFormContent');
            const formData = new FormData(form);

            // Проверяем наличие CSRF-токена в данных формы
            const csrfToken = formData.get('csrf_token');
            if (!csrfToken) {
                console.error("Ошибка CSRF-токена: токен отсутствует.");
                showErrorMessage('error', 'Ошибка', 'Ошибка 0101: Ошибка сервера.', 10000);
                return;
            }

            // Получаем ID пользователей и выбранные привилегии
            const userIDs = formData.get('userIDAssign').split(',').map(id => id.trim());
            const privileges = Array.from(selectElement.selectedOptions).map(opt => opt.value);

            // Формируем объект данных для отправки на сервер
            const data = {
                csrf_token: csrfToken,
                userIDs: userIDs,
                privileges: privileges
            };

            // Отправляем данные на сервер с помощью Fetch API
            fetch('back/authority_manager/AssignPrivileges.php', {
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
                    // Если успешно, показываем сообщение об успехе и закрываем форму
                    showErrorMessage('success', 'Успех', 'Полномочия успешно назначены!', 5000);
                    closeForm('assignPrivilegesForm');
                    form.reset();
                } else {
                    // Если произошла ошибка, показываем сообщение об ошибке
                    showErrorMessage('error', 'Ошибка', result.message || 'Ошибка 0102: Произошла неизвестная ошибка.', 10000);
                }
            })
            .catch(error => {
                // Обрабатываем ошибки при отправке данных
                showErrorMessage('error', 'Ошибка', 'Ошибка 0103: Произошла неизвестная ошибка.', 10000);
            });
        });
    }
});

// Функция для открытия формы по её ID
function openForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.style.display = 'block';
    }
}

// Функция для закрытия формы по её ID
function closeForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.style.display = 'none';
    }
}