document.addEventListener("DOMContentLoaded", function () {
    const viewAllPrivilegesButton = document.getElementById('ViewAllPrivileges');
    const viewAllPrivilegesForm = document.getElementById('viewAllPrivilegesForm');
    const tableContainer = document.querySelector('#viewAllPrivilegesFormContent');

    if (viewAllPrivilegesButton && viewAllPrivilegesForm && tableContainer) {
        // Логирование открытия формы
        viewAllPrivilegesButton.addEventListener('click', function () {
            console.log("Открыта форма просмотра всех полномочий.");
            fetchAllPrivileges();
            openForm('viewAllPrivilegesForm'); // Используем функцию openForm
        });

        // Логирование закрытия формы
        const closeButton = document.getElementById('closeViewAllPrivilegesForm');
        if (closeButton) {
            closeButton.addEventListener('click', function () {
                console.log("Форма просмотра всех полномочий закрыта.");
                closeForm('viewAllPrivilegesForm'); // Используем функцию closeForm
            });
        }

        // Функция для получения всех полномочий
        function fetchAllPrivileges() {
            console.log("Запрос всех полномочий...");
            fetch('back/authority_manager/get_all_privileges.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error("Ошибка сети или сервера.");
                }
                return response.text(); // Получаем HTML-код
            })
            .then(html => {
                console.log("HTML-таблица получена:", html);
                tableContainer.innerHTML = html; // Вставляем HTML в контейнер
            })
            .catch(error => {
                console.log("Ошибка при получении данных:", error.message);
                showErrorMessage('Ошибка', 'Произошла ошибка при получении данных.');
            });
        }
    }
});