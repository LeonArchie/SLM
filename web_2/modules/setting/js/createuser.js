document.getElementById('addButton').addEventListener('click', openAddForm);

function openAddForm() {
    document.getElementById('addFormOverlay').style.display = 'flex';
}

function closeAddForm() {
    document.getElementById('addFormOverlay').style.display = 'none';
    document.getElementById('addUserForm').reset();
}

document.getElementById('generatePassword').addEventListener('click', function () {
    const passwordField = document.getElementById('password');
    const generatedPassword = generateRandomPassword(10);
    passwordField.value = generatedPassword;
});

function generateRandomPassword(length) {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let result = '';
    for (let i = 0; i < length; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return result;
}

document.getElementById('addUserForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const formData = {
        full_name: document.getElementById('full_name').value,
        userlogin: document.getElementById('userlogin').value,
        password: document.getElementById('password').value,
        email: document.getElementById('email').value
    };

    fetch('back/create_user.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Пользователь успешно создан!');
            closeAddForm();
            location.reload();
        } else {
            showErrorMessage(data.message || 'Ошибка при создании пользователя.');
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
        showErrorMessage('Произошла ошибка при попытке создания пользователя.');
    });
});