document.addEventListener("DOMContentLoaded", function () {
    const revokePrivilegesButton = document.getElementById('OffPrivileges');
    const revokePrivilegesForm = document.getElementById('revokePrivilegesForm');

    if (revokePrivilegesButton && revokePrivilegesForm) {
        // Логирование открытия формы
        revokePrivilegesButton.addEventListener('click', function () {
            console.log("Открыта форма снятия полномочий.");
            const selectedUserIDs = Array.from(document.querySelectorAll('.userCheckbox:checked')).map(checkbox => checkbox.dataset.userid);
            document.getElementById('userIDRevoke').value = selectedUserIDs.join(', ');
            openForm('revokePrivilegesForm'); // Используем функцию openForm
        });

        // Логирование закрытия формы
        const cancelButton = document.getElementById('cancelRevokePrivilegesForm');
        if (cancelButton) {
            cancelButton.addEventListener('click', function () {
                console.log("Форма снятия полномочий закрыта.");
                closeForm('revokePrivilegesForm'); // Используем функцию closeForm
                document.getElementById('revokePrivilegesFormContent').reset();
            });
        }

        // Логирование отправки формы
        const submitButton = document.getElementById('submitRevokePrivilegesForm');
        if (submitButton) {
            submitButton.addEventListener('click', function () {
                console.log("Попытка снятия полномочий...");
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
                        console.log("Полномочия успешно сняты.");
                        showNotification('success', 'Успех', 'Полномочия сняты успешно!', 5000);
                        closeForm('revokePrivilegesForm'); // Закрываем форму после успешного снятия
                        document.getElementById('revokePrivilegesFormContent').reset();
                    } else {
                        console.log("Ошибка при снятии полномочий:", result.message);
                        showErrorMessage('Ошибка', 'Произошла ошибка при снятии полномочий.');
                    }
                })
                .catch(error => {
                    console.log("Ошибка при отправке данных:", error.message);
                    showErrorMessage('Ошибка', 'Произошла ошибка при отправке данных.');
                });
            });
        }
    }
});