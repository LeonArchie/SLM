document.addEventListener("DOMContentLoaded", function () {
    const createPrivilegesButton = document.getElementById('CreatePrivileges');
    const createPrivilegesForm = document.getElementById('createPrivilegesForm');

    if (createPrivilegesButton && createPrivilegesForm) {
        createPrivilegesButton.addEventListener('click', function () {
            openForm('createPrivilegesForm');
        });

        const cancelButton = document.getElementById('cancelCreatePrivilegesForm');
        if (cancelButton) {
            cancelButton.addEventListener('click', function () {
                closeForm('createPrivilegesForm');
                document.getElementById('createPrivilegesFormContent').reset();
            });
        }

        const submitButton = document.getElementById('submitCreatePrivilegesForm');
        if (submitButton) {
            submitButton.addEventListener('click', function () {
                const formData = new FormData(document.getElementById('createPrivilegesFormContent'));
                const data = {
                    privilegeName: formData.get('privilegeName'),
                    privilegeID: formData.get('privilegeID'),
                    csrf_token: formData.get('csrf_token')
                };

                fetch('back/authority_manager/create_privileges.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json;charset=UTF-8'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json()) // Преобразуем ответ в JSON
                .then(result => {
                    if (result.success) {
                        showErrorMessage('success', 'Успех', 'Привилегия создана успешно!', 5000);
                        closeForm('createPrivilegesForm');
                        document.getElementById('createPrivilegesFormContent').reset();
                    } else {
                        // Если success === false, показываем сообщение об ошибке
                        showErrorMessage('error', 'Ошибка', result.message, 10000);
                    }
                })
                .catch(error => {
                    console.error('Ошибка:', error);
                    showErrorMessage('error', 'Ошибка', 'Произошла ошибка при отправке данных.', 10000);
                });
            });
        }
    }
});

function openForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.style.display = "block";
        form.classList.remove("closing");
        form.classList.add("open");
    }
}

function closeForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.classList.add("closing");
        form.classList.remove("open");
        form.addEventListener("animationend", () => {
            if (form.classList.contains("closing")) {
                form.style.display = "none";
            }
        }, { once: true });
    }
}