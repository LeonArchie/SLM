document.addEventListener("DOMContentLoaded", function () {
    const deletePrivilegesButton = document.getElementById('DeletePrivileges');
    const deletePrivilegesForm = document.getElementById('deletePrivilegesForm');
    const selectElement = document.getElementById('privilegesToDelete');

    if (deletePrivilegesButton && deletePrivilegesForm && selectElement) {
        deletePrivilegesButton.addEventListener('click', function () {
            openForm('deletePrivilegesForm'); 
        });

        const cancelButton = document.getElementById('cancelDeletePrivilegesForm');
        if (cancelButton) {
            cancelButton.addEventListener('click', function () {
                closeForm('deletePrivilegesForm'); 
                document.getElementById('deletePrivilegesFormContent').reset();
            });
        }

        selectElement.addEventListener('mousedown', function (event) {
            const option = event.target;

            if (option.tagName === 'OPTION') {
                event.preventDefault();
                option.selected = !option.selected;
                const selectedValues = Array.from(selectElement.selectedOptions).map(opt => opt.value);
            }
        });

        const submitButton = document.getElementById('submitDeletePrivilegesForm');
        if (submitButton) {
            submitButton.addEventListener('click', function () {
                const form = document.getElementById('deletePrivilegesFormContent');
                const formData = new FormData(form);

                const csrfToken = formData.get('csrf_token');
                if (!csrfToken) {
                    showErrorMessage('error', 'Ошибка', 'Ошибка CSRF-токена.', 10000);
                    return;
                }

                const privileges = Array.from(selectElement.selectedOptions).map(opt => opt.value);

                const data = {
                    csrf_token: csrfToken,
                    privileges: privileges
                };

                fetch('back/authority_manager/delete_privileges.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json;charset=UTF-8'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error("Ошибка сети или сервера.");
                    }
                    return response.json();
                })
                .then(result => {
                    if (result.success) {
                        console.log("Полномочия успешно удалены:", result);
                        showErrorMessage('success', 'Успех', 'Полномочия успешно удалены!', 5000);
                        closeForm('deletePrivilegesForm');
                        form.reset();
                    } else {
                        showErrorMessage('error', 'Ошибка', result.message || 'Произошла ошибка при удалении полномочий.', 10000);
                    }
                })
                .catch(error => {
                    showErrorMessage('error', 'Ошибка', 'Произошла ошибка при отправке данных.', 10000);
                });
            });
        }
    }
});

function openForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.style.display = 'block';
        console.log(`Форма ${formId} открыта.`);
    }
}

function closeForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.style.display = 'none';
        console.log(`Форма ${formId} закрыта.`);
    }
}