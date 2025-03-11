document.addEventListener("DOMContentLoaded", function () {
    const viewPrivilegesButton = document.getElementById('VievPrivileges');
    const viewPrivilegesForm = document.getElementById('viewPrivilegesForm');

    if (viewPrivilegesButton && viewPrivilegesForm) {
        viewPrivilegesButton.addEventListener('click', function () {
            const selectedUserIDs = Array.from(document.querySelectorAll('.userCheckbox:checked')).map(checkbox => checkbox.dataset.userid);
            if (selectedUserIDs.length === 0) {
                showErrorMessage('warning', 'Внимание', 'Выберите хотя бы одного пользователя.', 7000);
                return;
            }
            const userID = selectedUserIDs[0];
            document.getElementById('userIDView').value = userID;
            fetchUserPrivileges(userID);
            openForm('viewPrivilegesForm');
        });

        const closeButton = document.getElementById('closeViewPrivilegesForm');
        if (closeButton) {
            closeButton.addEventListener('click', function () {
                closeForm('viewPrivilegesForm');
            });
        }

        function fetchUserPrivileges(userID) {
            if (!userID || typeof userID !== 'string') {
                showErrorMessage('error', 'Ошибка', 'Неверный идентификатор пользователя.', 10000);
                return;
            }

            fetch(`back/authority_manager/get_user_privileges.php?userID=${userID}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error("Ошибка сети или сервера.");
                    }
                    return response.text();
                })
                .then(html => {
                    const tableContainer = document.getElementById('privilegesTableContainer');
                    tableContainer.innerHTML = html; 
                })
                .catch(error => {
                    showErrorMessage('error', 'Ошибка', 'Произошла ошибка при получении данных.', 10000);
                });
        }
    }
});

function openForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.style.display = 'block';
    }
}

// Функция для закрытия формы
function closeForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.style.display = 'none';
    }
}