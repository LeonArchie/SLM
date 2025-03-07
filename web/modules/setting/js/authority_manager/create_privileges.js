document.addEventListener("DOMContentLoaded", function () {
    const createPrivilegesButton = document.getElementById('CreatePrivileges');
    const createPrivilegesForm = document.getElementById('createPrivilegesForm');

    if (createPrivilegesButton && createPrivilegesForm) {
        // Логирование открытия формы
        createPrivilegesButton.addEventListener('click', function () {
            console.log("Открыта форма создания полномочий.");
            openForm('createPrivilegesForm'); // Используем функцию openForm
        });

        // Логирование закрытия формы
        const cancelButton = document.getElementById('cancelCreatePrivilegesForm');
        if (cancelButton) {
            cancelButton.addEventListener('click', function () {
                console.log("Форма создания полномочий закрыта.");
                closeForm('createPrivilegesForm'); // Используем функцию closeForm
                document.getElementById('createPrivilegesFormContent').reset();
            });
        }

        // Логирование отправки формы
        const submitButton = document.getElementById('submitCreatePrivilegesForm');
        if (submitButton) {
            submitButton.addEventListener('click', function () {
                console.log("Попытка создания полномочий...");
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
                        console.log("Полномочия успешно созданы.");
                        showNotification('success', 'Успех', 'Привилегия создана успешно!', 5000);
                        closeForm('createPrivilegesForm'); // Закрываем форму после успешного создания
                        document.getElementById('createPrivilegesFormContent').reset();
                    } else {
                        console.log("Ошибка при создании полномочий:", result.message);
                        showErrorMessage('Ошибка', 'Произошла ошибка при создании привилегии.');
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

// Функция для открытия формы с анимацией
function openForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.style.display = "block";
        form.classList.remove("closing"); // Убираем класс закрытия
        form.classList.add("open"); // Добавляем класс открытия
    }
}

// Функция для закрытия формы с анимацией
function closeForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.classList.add("closing"); // Добавляем класс закрытия
        form.classList.remove("open"); // Убираем класс открытия
        // Ждем завершения анимации перед скрытием формы
        form.addEventListener("animationend", () => {
            if (form.classList.contains("closing")) {
                form.style.display = "none";
            }
        }, { once: true });
    }
}