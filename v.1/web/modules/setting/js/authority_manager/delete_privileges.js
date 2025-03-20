// Ожидаем полной загрузки DOM перед выполнением скрипта
document.addEventListener("DOMContentLoaded", function () {
    // Получаем элементы DOM: кнопку удаления привилегий, форму и выпадающий список
    const deletePrivilegesButton = document.getElementById('DeletePrivileges');
    const deletePrivilegesForm = document.getElementById('deletePrivilegesForm');
    const selectElement = document.getElementById('privilegesToDelete');

    // Проверяем, что все необходимые элементы существуют
    if (deletePrivilegesButton && deletePrivilegesForm && selectElement) {
        // Добавляем обработчик события на кнопку удаления привилегий для открытия формы
        deletePrivilegesButton.addEventListener('click', function () {
            openForm('deletePrivilegesForm'); 
        });

        // Получаем кнопку отмены и добавляем обработчик события для закрытия формы и сброса её содержимого
        const cancelButton = document.getElementById('cancelDeletePrivilegesForm');
        if (cancelButton) {
            cancelButton.addEventListener('click', function () {
                closeForm('deletePrivilegesForm'); 
                document.getElementById('deletePrivilegesFormContent').reset();
            });
        }

        // Добавляем обработчик события на выпадающий список для множественного выбора элементов
        selectElement.addEventListener('mousedown', function (event) {
            const option = event.target;

            // Проверяем, что клик был по элементу <option>
            if (option.tagName === 'OPTION') {
                event.preventDefault(); // Предотвращаем стандартное поведение
                option.selected = !option.selected; // Переключаем состояние выбора
                const selectedValues = Array.from(selectElement.selectedOptions).map(opt => opt.value);
            }
        });

        // Получаем кнопку отправки формы и добавляем обработчик события для отправки данных
        const submitButton = document.getElementById('submitDeletePrivilegesForm');
        if (submitButton) {
            submitButton.addEventListener('click', function () {
                const form = document.getElementById('deletePrivilegesFormContent');
                const formData = new FormData(form);

                // Получаем CSRF-токен из формы
                const csrfToken = formData.get('csrf_token');
                if (!csrfToken) {
                    // Если CSRF-токен отсутствует, показываем сообщение об ошибке
                    showErrorMessage('error', 'Ошибка', 'Ошибка 0096: Ошибка сервера.', 10000);
                    return;
                }

                // Получаем выбранные привилегии из выпадающего списка
                const privileges = Array.from(selectElement.selectedOptions).map(opt => opt.value);

                // Формируем данные для отправки на сервер
                const data = {
                    csrf_token: csrfToken,
                    privileges: privileges
                };

                // Отправляем данные на сервер с помощью fetch
                fetch('back/authority_manager/delete_privileges.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json;charset=UTF-8'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => {
                    // Проверяем, что ответ от сервера успешный
                    if (!response.ok) {
                        throw new Error("Ошибка сети или сервера.");
                    }
                    return response.json();
                })
                .then(result => {
                    // Обрабатываем результат ответа от сервера
                    if (result.success) {
                        console.log("Полномочия успешно удалены:", result);
                        showErrorMessage('success', 'Успех', 'Полномочия успешно удалены!', 5000);
                        closeForm('deletePrivilegesForm');
                        form.reset();
                        location.reload(); // Перезагружаем страницу
                    } else {
                        showErrorMessage('error', 'Ошибка', result.message || 'Ошибка 0097: Произошла неизвестная ошибка.', 10000);
                    }
                })
                .catch(error => {
                    // Обрабатываем ошибки, возникшие при отправке данных
                    showErrorMessage('error', 'Ошибка', 'Ошибка 0098: Произошла неизвестная ошибка.', 10000);
                });
            });
        }
    }
});

// Функция для открытия формы по её ID
function openForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.style.display = 'block';
    }
}

// Функция для закрытия формы по её ID
function closeForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.style.display = 'none';
    }
}