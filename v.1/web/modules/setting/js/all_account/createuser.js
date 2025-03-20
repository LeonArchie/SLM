// Ожидаем полной загрузки DOM перед выполнением скрипта
document.addEventListener("DOMContentLoaded", function () {
    // Получаем элементы DOM
    const generateButton = document.getElementById("generate-password");
    const passwordField = document.getElementById("password");
    const addUserForm = document.getElementById('addUserForm');
    const addButton = document.getElementById('addButton');
    const cancelButton = document.querySelector('.cancel');
    const addFormOverlay = document.getElementById('addFormOverlay');
    const csrfToken = document.querySelector('input[name="csrf_token"]')?.value;

    // Проверяем, существуют ли необходимые элементы на странице
    if (!generateButton || !passwordField || !addUserForm || !addButton || !cancelButton || !addFormOverlay) {
        console.error('Необходимые элементы не найдены на странице.');
        return;
    }

    // Добавляем обработчик события на кнопку генерации пароля
    generateButton.addEventListener("click", function () {
        const randomPassword = generateRandomPassword(12); // Генерация пароля длиной 12 символов
        passwordField.value = randomPassword;
    });

    // Добавляем обработчик события на кнопку "Добавить"
    addButton.addEventListener('click', openAddForm);

    // Функция для открытия формы добавления пользователя
    function openAddForm() {
        addFormOverlay.style.display = 'flex';
    }

    // Функция для закрытия формы добавления пользователя
    window.closeAddForm = function () {
        addFormOverlay.style.display = 'none';
        addUserForm.reset();
    };

    // Добавляем обработчик события на кнопку "Отмена"
    cancelButton.addEventListener('click', closeAddForm);

    // Добавляем обработчик события на отправку формы
    addUserForm.addEventListener('submit', function (e) {
        e.preventDefault();

        // Сбор данных из формы
        const formData = {
            full_name: document.getElementById('full_name').value.trim(),
            userlogin: document.getElementById('userlogin').value.trim(),
            password: document.getElementById('password').value.trim(),
            email: document.getElementById('email').value.trim(),
            role: document.getElementById('role').value.trim(),
            csrf_token: csrfToken
        };

        // Валидация данных
        const validationIssues = validateFormData(formData);
        if (validationIssues.length > 0) {
            showErrorMessage('warning', 'Внимание', 'Ошибка 0064: ' +  validationIssues.join('\n'), 5000); // Показываем все ошибки
            return;
        }

        // Отправка данных на сервер
        fetch('back/all_account/create_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json; charset=utf-8'
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showErrorMessage('success', 'Успех', 'Пользователь успешно создан!', 3000);
                closeAddForm();
            } else {
                // Используем сообщение об ошибке, которое пришло от сервера
                showErrorMessage('error', 'Ошибка', data.message || 'Ошибка 0065: Произошла неизвестная ошибка.', 5000);
            }
        })
        .catch(error => {
            // В случае ошибки сети или других проблем с запросом
            showErrorMessage('error', 'Ошибка', 'Ошибка 0066: Произошла неизвестная ошибка.', 5000);
        });
    });

    /**
     * Генерирует случайный пароль заданной длины.
     * Пароль содержит как минимум одну строчную букву, одну заглавную букву,
     * одну цифру и один специальный символ.
     * @param {number} length - Длина пароля.
     * @returns {string} Сгенерированный пароль.
     */
    function generateRandomPassword(length) {
        const lowercase = "abcdefghijklmnopqrstuvwxyz";
        const uppercase = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        const numbers = "0123456789";
        const specialChars = "!@#$%^&*()_+";
        const allChars = lowercase + uppercase + numbers + specialChars;

        let password = "";
        let hasLower = false, hasUpper = false, hasNumber = false, hasSpecial = false;

        while (password.length < length || !(hasLower && hasUpper && hasNumber && hasSpecial)) {
            const randomIndex = Math.floor(Math.random() * allChars.length);
            const char = allChars[randomIndex];

            if (!hasLower && lowercase.includes(char)) hasLower = true;
            if (!hasUpper && uppercase.includes(char)) hasUpper = true;
            if (!hasNumber && numbers.includes(char)) hasNumber = true;
            if (!hasSpecial && specialChars.includes(char)) hasSpecial = true;

            password += char;
        }

        return password.slice(0, length); // Обрезаем до нужной длины
    }

    /**
     * Проверяет данные формы на соответствие требованиям.
     * @param {object} formData - Данные формы.
     * @returns {string[]} Массив сообщений об ошибках.
     */
    function validateFormData(formData) {
        const validationIssues = [];

        // Валидация полного ФИО
        if (formData.full_name.length > 50) {
            validationIssues.push('Полное ФИО превышает допустимую длину (максимум 50 символов).');
        } else if (!/^[\p{Script=Cyrillic}\s]+$/u.test(formData.full_name)) {
            validationIssues.push('Полное ФИО содержит недопустимые символы (разрешены только русские буквы и пробелы).');
        }

        // Валидация логина
        if (formData.userlogin.length > 20) {
            validationIssues.push('Логин превышает допустимую длину (максимум 20 символов).');
        } else if (!/^[a-zA-Z0-9_]+$/.test(formData.userlogin)) {
            validationIssues.push('Логин содержит недопустимые символы (разрешены только латинские буквы, цифры и "_").');
        }

        // Валидация пароля
        if (formData.password.length < 10) {
            validationIssues.push('Пароль слишком короткий (минимум 10 символов).');
        } else if (formData.password === formData.userlogin) {
            validationIssues.push('Пароль не должен совпадать с логином.');
        }

        // Валидация email
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
            validationIssues.push('Некорректный формат email.');
        }

        return validationIssues;
    }
});