document.addEventListener("DOMContentLoaded", function () {
    const revokePrivilegesButton = document.getElementById('OffPrivileges');
    const revokePrivilegesForm = document.getElementById('revokePrivilegesForm');

    if (revokePrivilegesButton && revokePrivilegesForm) {
        revokePrivilegesButton.addEventListener('click', function () {
            const selectedUserIDs = Array.from(document.querySelectorAll('.userCheckbox:checked')).map(checkbox => checkbox.dataset.userid);
            document.getElementById('userIDRevoke').value = selectedUserIDs.join(', ');
            openForm('revokePrivilegesForm');
        });

        const cancelButton = document.getElementById('cancelRevokePrivilegesForm');
        if (cancelButton) {
            cancelButton.addEventListener('click', function () {
                closeForm('revokePrivilegesForm');
                document.getElementById('revokePrivilegesFormContent').reset();
            });
        }

        const submitButton = document.getElementById('submitRevokePrivilegesForm');
        if (submitButton) {
            submitButton.addEventListener('click', function () {
                const formData = new FormData(document.getElementById('revokePrivilegesFormContent'));
                const data = {
                    userIDRevoke: formData.get('userIDRevoke'),
                    privilegesToRevoke: Array.from(formData.getAll('privilegesToRevoke[]')),
                    csrf_token: formData.get('csrf_token')
                };

                fetch('back/authority_manager/revoke_privileges.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json;charset=UTF-8'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        showErrorMessage('success', 'Успех', 'Полномочия сняты успешно!', 5000);
                        closeForm('revokePrivilegesForm');
                        document.getElementById('revokePrivilegesFormContent').reset();
                    } else {
                        showErrorMessage('Ошибка', 'Произошла ошибка при снятии полномочий.');
                    }
                })
                .catch(error => {
                    showErrorMessage('Ошибка', 'Произошла ошибка при отправке данных.');
                });
            });
        }
    }
});