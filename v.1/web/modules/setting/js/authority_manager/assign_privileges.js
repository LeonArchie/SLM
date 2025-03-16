document.addEventListener("DOMContentLoaded", function () {
    const assignPrivilegesButton = document.getElementById('AssignPrivileges');
    const assignPrivilegesForm = document.getElementById('assignPrivilegesForm');
    const selectElement = document.getElementById('privilegesToAssign');

    if (assignPrivilegesButton && assignPrivilegesForm && selectElement) {
        assignPrivilegesButton.addEventListener('click', function () {

            // Получаем UserID
            const selectedUserIDs = Array.from(document.querySelectorAll('.userCheckbox:checked')).map(checkbox => checkbox.dataset.userid);

            if (selectedUserIDs.length === 0) {
                showErrorMessage('warning', 'Внимание', 'Выберите хотя бы одного пользователя.', 7000);
                return;
            }

            const userID = selectedUserIDs.join(', ');
            document.getElementById('userIDAssign').value = userID;
            openForm('assignPrivilegesForm');
        });

        const cancelButton = document.getElementById('cancelAssignPrivilegesForm');
        if (cancelButton) {
            cancelButton.addEventListener('click', function () {
                closeForm('assignPrivilegesForm');
                document.getElementById('assignPrivilegesFormContent').reset();
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

        document.getElementById('submitAssignPrivilegesForm').addEventListener('click', function () {
            const form = document.getElementById('assignPrivilegesFormContent');
            const formData = new FormData(form);

            const csrfToken = formData.get('csrf_token');
            if (!csrfToken) {
                console.error("Ошибка CSRF-токена: токен отсутствует.");
                showErrorMessage('error', 'Ошибка', 'Ошибка CSRF-токена.', 10000);
                return;
            }

            const userIDs = formData.get('userIDAssign').split(',').map(id => id.trim());
            const privileges = Array.from(selectElement.selectedOptions).map(opt => opt.value);

            const data = {
                csrf_token: csrfToken,
                userIDs: userIDs,
                privileges: privileges
            };

            fetch('back/authority_manager/AssignPrivileges.php', {
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
                    showErrorMessage('success', 'Успех', 'Полномочия успешно назначены!', 5000);
                    closeForm('assignPrivilegesForm');
                    form.reset();
                } else {
                    showErrorMessage('error', 'Ошибка', result.message || 'Произошла ошибка при назначении полномочий.', 10000);
                }
            })
            .catch(error => {
                showErrorMessage('error', 'Ошибка', 'Произошла ошибка при отправке данных.', 10000);
            });
        });
    }
});

function openForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.style.display = 'block';
    }
}

function closeForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.style.display = 'none';
    }
}