document.addEventListener("DOMContentLoaded", function () {
    // Получаем кнопку "Редактировать"
    const editButton = document.getElementById('editButton');

    // Обработчик для кнопки "Редактировать"
    editButton.addEventListener('click', function () {
        const selectedCheckbox = document.querySelector('.userCheckbox:checked');
        if (selectedCheckbox) {
            const userId = selectedCheckbox.dataset.userid;
            redirectToEditUser(userId); // Отправляем userid на страницу редактирования
        }
    });

    // Функция для отправки userid через POST и перенаправления на edituser.php
    function redirectToEditUser(userid) {
        // Создаем скрытую форму
        const form = document.createElement('form');
        form.method = 'POST'; // Указываем метод POST
        form.action = 'edituser.php'; // Указываем адрес обработчика
        form.style.display = 'none'; // Скрываем форму

        // Создаем скрытое поле для userid
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'userid';
        input.value = userid;

        // Добавляем поле в форму
        form.appendChild(input);

        // Добавляем форму в тело документа
        document.body.appendChild(form);

        // Отправляем форму
        form.submit();
    }
});