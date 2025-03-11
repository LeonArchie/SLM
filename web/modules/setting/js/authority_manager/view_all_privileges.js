document.addEventListener("DOMContentLoaded", function () {
    const viewAllPrivilegesButton = document.getElementById('ViewAllPrivileges');
    const viewAllPrivilegesForm = document.getElementById('viewAllPrivilegesForm');
    const tableContainer = document.querySelector('#viewAllPrivilegesFormContent');

    if (viewAllPrivilegesButton && viewAllPrivilegesForm && tableContainer) {
        viewAllPrivilegesButton.addEventListener('click', function () {
            fetchAllPrivileges();
            openForm('viewAllPrivilegesForm'); 
        });

        // Логирование закрытия формы
        const closeButton = document.getElementById('closeViewAllPrivilegesForm');
        if (closeButton) {
            closeButton.addEventListener('click', function () {
                closeForm('viewAllPrivilegesForm');
            });
        }

        function fetchAllPrivileges() {
            console.log("Запрос всех полномочий...");
            fetch('back/authority_manager/get_all_privileges.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error("Ошибка сети или сервера.");
                }
                return response.text();
            })
            .then(html => {
                tableContainer.innerHTML = html; 
            })
            .catch(error => {
                showErrorMessage('Ошибка', 'Произошла ошибка при получении данных.');
            });
        }
    }
});