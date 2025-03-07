document.addEventListener("DOMContentLoaded", function () {
    const viewPrivilegesButton = document.getElementById('VievPrivileges');
    const viewPrivilegesForm = document.getElementById('viewPrivilegesForm');

    if (viewPrivilegesButton && viewPrivilegesForm) {
        // Логирование открытия формы
        viewPrivilegesButton.addEventListener('click', function () {
            console.log("Открыта форма просмотра полномочий.");
            const selectedUserIDs = Array.from(document.querySelectorAll('.userCheckbox:checked')).map(checkbox => checkbox.dataset.userid);
            if (selectedUserIDs.length === 0) {
                showErrorMessage('warning', 'Внимание', 'Выберите хотя бы одного пользователя.', 7000);
                return;
            }
            const userID = selectedUserIDs[0]; // Берем первого выбранного пользователя
            console.log("Selected UserID:", userID);
            document.getElementById('userIDView').value = userID;
            fetchUserPrivileges(userID);
            openForm('viewPrivilegesForm'); // Используем функцию openForm
        });

        // Логирование закрытия формы
        const closeButton = document.getElementById('closeViewPrivilegesForm');
        if (closeButton) {
            closeButton.addEventListener('click', function () {
                console.log("Форма просмотра полномочий закрыта.");
                closeForm('viewPrivilegesForm'); // Используем функцию closeForm
            });
        }

        // Функция для получения данных о полномочиях
        function fetchUserPrivileges(userID) {
            // Проверяем, что userID не пустой
            if (!userID || typeof userID !== 'string') {
                console.log("Неверный UserID:", userID);
                showErrorMessage('error', 'Ошибка', 'Неверный идентификатор пользователя.', 10000);
                return;
            }

            console.log("Запрос данных о полномочиях для UserID:", userID);
            fetch(`back/authority_manager/get_user_privileges.php?userID=${userID}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error("Ошибка сети или сервера.");
                    }
                    return response.text(); // Получаем HTML-код
                })
                .then(html => {
                    console.log("HTML-таблица получена:", html);
                    const tableContainer = document.getElementById('privilegesTableContainer');
                    tableContainer.innerHTML = html; // Вставляем HTML в контейнер
                })
                .catch(error => {
                    console.log("Ошибка при получении данных:", error.message);
                    showErrorMessage('error', 'Ошибка', 'Произошла ошибка при получении данных.', 10000);
                });
        }
    }
});

// Функция для открытия формы
function openForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.style.display = 'block'; // Показываем форму
    } else {
        console.error("Форма не найдена:", formId);
    }
}

// Функция для закрытия формы
function closeForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.style.display = 'none'; // Скрываем форму
    } else {
        console.error("Форма не найдена:", formId);
    }
}