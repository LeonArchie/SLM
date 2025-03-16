document.addEventListener("DOMContentLoaded", function () {

    const generateButton = document.getElementById("generate-password");
    const passwordField = document.getElementById("password");

    if (!generateButton || !passwordField) {
        return;
    }

    generateButton.addEventListener("click", function () {
        const randomPassword = generateRandomPassword(10);
        passwordField.value = randomPassword;
    });

    function generateRandomPassword(length) {
        const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        let password = "";

        for (let i = 0; i < length; i++) {
            const randomIndex = Math.floor(Math.random() * charset.length);
            password += charset[randomIndex];
        }
        return password;
    }

    document.getElementById('addButton').addEventListener('click', openAddForm);

    function openAddForm() {
        document.getElementById('addFormOverlay').style.display = 'flex';
    }

    window.closeAddForm = function () {
        document.getElementById('addFormOverlay').style.display = 'none';
        document.getElementById('addUserForm').reset();
    };

    document.querySelector('.cancel').addEventListener('click', closeAddForm);

    document.getElementById('addUserForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const csrfToken = document.getElementsByName('csrf_token')[0]?.value;
        if (!csrfToken) {
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
                showErrorMessage('Пользователь успешно создан!');
                closeAddForm();
                location.reload();
            } else {
                showErrorMessage(data.message);
            }
        })
        .catch(error => {
            showErrorMessage('Ошибка при отправке данных');
        });
    });
});