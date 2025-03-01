document.addEventListener("DOMContentLoaded", function () {
    console.log('Скрипт загружен и готов к работе.');

    // Генерация пароля
    const generateButton = document.getElementById("generate-password");
    const passwordField = document.getElementById("password");

    if (!generateButton || !passwordField) {
        console.error('Элементы для генерации пароля не найдены.');
        return;
    }

    console.log('Элементы для генерации пароля найдены.');

    generateButton.addEventListener("click", function () {
        console.log('Нажата кнопка генерации пароля.');
        const randomPassword = generateRandomPassword(10);
        passwordField.value = randomPassword;
        console.log(`Сгенерирован пароль: ${randomPassword}`);
    });

    function generateRandomPassword(length) {
        console.log('Начало генерации случайного пароля.');
        const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        let password = "";

        for (let i = 0; i < length; i++) {
            const randomIndex = Math.floor(Math.random() * charset.length);
            password += charset[randomIndex];
        }
        console.log('Пароль успешно сгенерирован.');
        return password;
    }

    // Открытие формы
    document.getElementById('addButton').addEventListener('click', openAddForm);

    function openAddForm() {
        console.log('Открыта форма добавления пользователя.');
        document.getElementById('addFormOverlay').style.display = 'flex';
    }

    // Закрытие формы
    window.closeAddForm = function () {
        console.log('Закрыта форма добавления пользователя.');
        document.getElementById('addFormOverlay').style.display = 'none';
        document.getElementById('addUserForm').reset();
    };

    // Добавление обработчика для кнопки "Отменить"
    document.querySelector('.cancel').addEventListener('click', closeAddForm);

    // AJAX-запрос для создания пользователя
    document.getElementById('addUserForm').addEventListener('submit', function (e) {
        e.preventDefault();

        // Проверка наличия CSRF-токена
        const csrfToken = document.getElementsByName('csrf_token')[0]?.value;
        if (!csrfToken) {
            console.error('CSRF-токен не найден. Пожалуйста, обновите страницу.');
            showErrorMessage('CSRF-токен не найден. Пожалуйста, обновите страницу.');
            return;
        }

        // Сбор данных из формы
        const formData = {
            csrf_token: csrfToken,
            full_name: document.getElementById('full_name').value.trim(),
            userlogin: document.getElementById('userlogin').value.trim(),
            password: document.getElementById('password').value.trim(),
            email: document.getElementById('email').value.trim(),
            role: document.getElementById('role').value.trim()
        };

        // Вывод данных в консоль перед отправкой
        console.group('Данные для отправки:');
        console.log('CSRF-токен:', formData.csrf_token);
        console.log('Полное ФИО:', formData.full_name);
        console.log('Логин:', formData.userlogin);
        console.log('E-mail:', formData.email);
        console.log('Роль:', formData.role);
        console.groupEnd();

        // Логирование данных в формате UTF-8
        console.log('Данные будут отправлены в формате UTF-8:', JSON.stringify(formData));

        console.log('Отправляемые данные:', formData);

        // Отправка данных на сервер
        fetch('back/create_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json; charset=utf-8' // Указываем кодировку UTF-8
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Успешный ответ от сервера:', data.message);
                showErrorMessage('Пользователь успешно создан!');
                closeAddForm();
            } else {
                console.error('Ошибка при создании пользователя:', data.message || 'Неизвестная ошибка.');
                showErrorMessage(data.message);
            }
        })
        .catch(error => {
            console.error('Ошибка при отправке данных:', error);
            showErrorMessage('Ошибка при отправке данных');
        });
    });
});