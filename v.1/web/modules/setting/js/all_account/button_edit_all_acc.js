// Ожидание полной загрузки DOM перед выполнением скрипта
document.addEventListener("DOMContentLoaded", function () {
    // Получение ссылки на кнопку редактирования по её ID
    const editButton = document.getElementById('editButton');

    // Добавление обработчика события нажатия на кнопку редактирования
    editButton.addEventListener('click', function () {
        // Поиск выбранного чекбокса с классом 'userCheckbox'
        const selectedCheckbox = document.querySelector('.userCheckbox:checked');
        
        // Проверка, выбран ли чекбокс
        if (selectedCheckbox) {
            // Получение ID пользователя из атрибута 'data-userid' выбранного чекбокса
            const userId = selectedCheckbox.dataset.userid;
            
            // Вызов функции для перенаправления на страницу редактирования пользователя
            redirectToEditUser(userId); 
        }
    });

    // Функция для перенаправления на страницу редактирования пользователя
    function redirectToEditUser(userid) {
        // Создание формы для отправки данных методом POST
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'edituser.php'; // Указание URL для отправки данных
        form.style.display = 'none'; // Скрытие формы

        // Создание скрытого поля для передачи ID пользователя
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'userid';
        input.value = userid;

        // Добавление скрытого поля в форму
        form.appendChild(input);

        // Добавление формы в тело документа
        document.body.appendChild(form);

        // Отправка формы
        form.submit();
    }
});