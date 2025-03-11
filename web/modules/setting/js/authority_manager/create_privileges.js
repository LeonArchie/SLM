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
                    pagesCheckbox: formData.get('pagesCheckbox') === 'on',
                    csrf_token: formData.get('csrf_token')
                };

                fetch('back/authority_manager/create_privileges.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json;charset=UTF-8'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        showErrorMessage('success', 'Успех', 'Привилегия создана успешно!', 5000);
                        closeForm('createPrivilegesForm'); 
                        document.getElementById('createPrivilegesFormContent').reset();
                    } else {
                        showErrorMessage('Ошибка', 'Произошла ошибка при создании привилегии.');
                    }
                })
                .catch(error => {
                    showErrorMessage('Ошибка', 'Произошла ошибка при отправке данных.');
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