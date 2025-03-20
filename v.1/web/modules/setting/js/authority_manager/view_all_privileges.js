// Ожидаем полной загрузки DOM перед выполнением скрипта
document.addEventListener("DOMContentLoaded", function () {
    // Получаем кнопку для просмотра всех привилегий
    const viewAllPrivilegesButton = document.getElementById('ViewAllPrivileges');
    // Получаем форму для отображения всех привилегий
    const viewAllPrivilegesForm = document.getElementById('viewAllPrivilegesForm');
    // Получаем контейнер для таблицы с привилегиями
    const tableContainer = document.querySelector('#viewAllPrivilegesFormContent');

    // Проверяем, существуют ли все необходимые элементы на странице
    if (viewAllPrivilegesButton && viewAllPrivilegesForm && tableContainer) {
        // Добавляем обработчик события нажатия на кнопку "Просмотреть все привилегии"
        viewAllPrivilegesButton.addEventListener('click', function () {
            // Вызываем функцию для получения всех привилегий
            fetchAllPrivileges();
            // Открываем форму для отображения привилегий
            openForm('viewAllPrivilegesForm'); 
        });

        // Логирование закрытия формы
        // Получаем кнопку закрытия формы
        const closeButton = document.getElementById('closeViewAllPrivilegesForm');
        if (closeButton) {
            // Добавляем обработчик события нажатия на кнопку закрытия формы
            closeButton.addEventListener('click', function () {
                // Закрываем форму
                closeForm('viewAllPrivilegesForm');
            });
        }

        // Функция для получения всех привилегий с сервера
        function fetchAllPrivileges() {
            // Выполняем запрос на сервер для получения всех привилегий
            fetch('back/authority_manager/get_all_privileges.php')
            .then(response => {
                // Проверяем, успешен ли запрос
                if (!response.ok) {
                    showErrorMessage('error', 'Ошибка', 'Ошибка 0090: Ошибка сервера.', 10000);
                }
                // Возвращаем ответ в виде текста (HTML)
                return response.text();
            })
            .then(html => {
                // Вставляем полученный HTML в контейнер для таблицы
                tableContainer.innerHTML = html; 
            })
            .catch(error => {
                // В случае ошибки показываем сообщение об ошибке
                showErrorMessage('error', 'Ошибка', 'Ошибка 0091: Ошибка сервера.', 10000);
            });
        }
    }
});