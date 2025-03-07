document.addEventListener("DOMContentLoaded", function () {
    const deletePrivilegesButton = document.getElementById('DeletePrivileges');
    const deletePrivilegesForm = document.getElementById('deletePrivilegesForm');
    const selectElement = document.getElementById('privilegesToDelete');

    if (deletePrivilegesButton && deletePrivilegesForm && selectElement) {
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
                document.getElementById('deletePrivilegesFormContent').reset(); // Очищаем форму
            });
        }

        // Обработка кликов на опциях
        selectElement.addEventListener('mousedown', function (event) {
            const option = event.target;

            // Если кликнули на option
            if (option.tagName === 'OPTION') {
                // Отменяем стандартное поведение
                event.preventDefault();

                // Переключаем состояние выбора
                option.selected = !option.selected;

                // Логируем выбранные значения
                const selectedValues = Array.from(selectElement.selectedOptions).map(opt => opt.value);
                console.log("Выбранные привилегии для удаления:", selectedValues);
            }
        });

        // Логирование отправки формы
        const submitButton = document.getElementById('submitDeletePrivilegesForm');
        if (submitButton) {
            submitButton.addEventListener('click', function () {
                console.log("Попытка удаления полномочий...");

                const form = document.getElementById('deletePrivilegesFormContent');
                const formData = new FormData(form);

                // Валидация CSRF-токена
                const csrfToken = formData.get('csrf_token');
                if (!csrfToken) {
                    console.error("Ошибка CSRF-токена: токен отсутствует.");
                    showErrorMessage('error', 'Ошибка', 'Ошибка CSRF-токена.', 10000);
                    return;
                }

                // Подготовка данных для отправки
                const privileges = Array.from(selectElement.selectedOptions).map(opt => opt.value);

                const data = {
                    csrf_token: csrfToken,
                    privileges: privileges
                };

                console.log("Отправка данных на сервер:", data);

                // Отправка данных на сервер
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
                        closeForm('deletePrivilegesForm'); // Закрываем форму после успешного удаления
                        form.reset();
                    } else {
                        console.error("Ошибка при удалении полномочий:", result.message);
                        showErrorMessage('error', 'Ошибка', result.message || 'Произошла ошибка при удалении полномочий.', 10000);
                    }
                })
                .catch(error => {
                    console.error('Ошибка при отправке данных:', error.message);
                    showErrorMessage('error', 'Ошибка', 'Произошла ошибка при отправке данных.', 10000);
                });
            });
        }
    }
});

// Функция для открытия формы
function openForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.style.display = 'block'; // Показываем форму
        console.log(`Форма ${formId} открыта.`);
    } else {
        console.error("Форма не найдена:", formId);
    }
}

// Функция для закрытия формы
function closeForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.style.display = 'none'; // Скрываем форму
        console.log(`Форма ${formId} закрыта.`);
    } else {
        console.error("Форма не найдена:", formId);
    }
}