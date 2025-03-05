document.addEventListener("DOMContentLoaded", function () {
    const deletePrivilegesButton = document.getElementById('DeletePrivileges');
    const deletePrivilegesForm = document.getElementById('deletePrivilegesForm');

    if (deletePrivilegesButton && deletePrivilegesForm) {
        // Логирование открытия формы
        deletePrivilegesButton.addEventListener('click', function () {
            console.log("Открыта форма удаления полномочий.");
            openForm('deletePrivilegesForm'); // Используем функцию openForm
        });

        // Логирование закрытия формы
        const cancelButton = document.getElementById('cancelDeletePrivilegesForm');
        if (cancelButton) {
            cancelButton.addEventListener('click', function () {
                console.log("Форма удаления полномочий закрыта.");
                closeForm('deletePrivilegesForm'); // Используем функцию closeForm
                document.getElementById('deletePrivilegesFormContent').reset();
            });
        }

        // Логирование отправки формы
        const submitButton = document.getElementById('submitDeletePrivilegesForm');
        if (submitButton) {
            submitButton.addEventListener('click', function () {
                console.log("Попытка удаления полномочий...");
                const formData = new FormData(document.getElementById('deletePrivilegesFormContent'));
                const data = {
                    privilegesToDelete: Array.from(formData.getAll('privilegesToDelete[]')),
                    csrf_token: formData.get('csrf_token')
                };

                fetch('back/delete_privileges.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json;charset=UTF-8'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        console.log("Полномочия успешно удалены.");
                        showNotification('success', 'Успех', 'Привилегии удалены успешно!', 5000);
                        closeForm('deletePrivilegesForm'); // Закрываем форму после успешного удаления
                        document.getElementById('deletePrivilegesFormContent').reset();
                    } else {
                        console.log("Ошибка при удалении полномочий:", result.message);
                        showErrorMessage('Ошибка', 'Произошла ошибка при удалении привилегий.');
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