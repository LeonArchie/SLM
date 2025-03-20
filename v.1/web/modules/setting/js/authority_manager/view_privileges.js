// Ожидаем полной загрузки DOM перед выполнением скрипта
document.addEventListener("DOMContentLoaded", function () {
    // Находим кнопку для просмотра привилегий и форму для отображения привилегий
    const viewPrivilegesButton = document.getElementById('VievPrivileges');
    const viewPrivilegesForm = document.getElementById('viewPrivilegesForm');

    // Проверяем, существуют ли кнопка и форма на странице
    if (viewPrivilegesButton && viewPrivilegesForm) {
        // Добавляем обработчик события нажатия на кнопку
        viewPrivilegesButton.addEventListener('click', function () {
            // Получаем выбранные ID пользователей из отмеченных чекбоксов
            const selectedUserIDs = Array.from(document.querySelectorAll('.userCheckbox:checked')).map(checkbox => checkbox.dataset.userid);
            
            // Если ни один пользователь не выбран, показываем сообщение об ошибке
            if (selectedUserIDs.length === 0) {
                showErrorMessage('warning', 'Внимание', 'Ошибка 0087: Выберите хотя бы одного пользователя.', 7000);
                return;
            }
            
            // Берем первый выбранный ID пользователя
            const userID = selectedUserIDs[0];
            
            // Устанавливаем значение ID пользователя в скрытое поле формы
            document.getElementById('userIDView').value = userID;
            
            // Запрашиваем привилегии для выбранного пользователя
            fetchUserPrivileges(userID);
            
            // Открываем форму для просмотра привилегий
            openForm('viewPrivilegesForm');
        });

        // Находим кнопку закрытия формы
        const closeButton = document.getElementById('closeViewPrivilegesForm');
        if (closeButton) {
            // Добавляем обработчик события нажатия на кнопку закрытия
            closeButton.addEventListener('click', function () {
                // Закрываем форму
                closeForm('viewPrivilegesForm');
            });
        }

        // Функция для запроса привилегий пользователя
        function fetchUserPrivileges(userID) {
            // Проверяем, что userID является строкой и не пуст
            if (!userID || typeof userID !== 'string') {
                showErrorMessage('error', 'Ошибка', 'Ошибка 0088: Обновите страницу и повторите попытку.', 10000);
                return;
            }

            // Выполняем запрос на сервер для получения привилегий пользователя
            fetch(`back/authority_manager/get_user_privileges.php?userID=${userID}`)
                .then(response => {
                    // Проверяем, что ответ от сервера успешный
                    if (!response.ok) {
                        throw new Error("Ошибка сети или сервера.");
                    }
                    return response.text();
                })
                .then(html => {
                    // Вставляем полученный HTML в контейнер для таблицы привилегий
                    const tableContainer = document.getElementById('privilegesTableContainer');
                    tableContainer.innerHTML = html; 
                })
                .catch(error => {
                    // В случае ошибки показываем сообщение об ошибке
                    showErrorMessage('error', 'Ошибка', 'Ошибка 0089: Ошибка сервера.', 10000);
                });
        }
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