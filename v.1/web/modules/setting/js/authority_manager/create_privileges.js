// Ожидаем, пока весь HTML-документ будет загружен и готов к взаимодействию
document.addEventListener("DOMContentLoaded", function () {
    // Получаем ссылки на кнопку создания привилегий и форму создания привилегий
    const createPrivilegesButton = document.getElementById('CreatePrivileges');
    const createPrivilegesForm = document.getElementById('createPrivilegesForm');

    // Проверяем, существуют ли кнопка и форма на странице
    if (createPrivilegesButton && createPrivilegesForm) {
        // Добавляем обработчик события на клик по кнопке создания привилегий
        createPrivilegesButton.addEventListener('click', function () {
            // Открываем форму создания привилегий
            openForm('createPrivilegesForm');
        });

        // Получаем ссылку на кнопку отмены в форме создания привилегий
        const cancelButton = document.getElementById('cancelCreatePrivilegesForm');
        if (cancelButton) {
            // Добавляем обработчик события на клик по кнопке отмены
            cancelButton.addEventListener('click', function () {
                // Закрываем форму и сбрасываем её содержимое
                closeForm('createPrivilegesForm');
                document.getElementById('createPrivilegesFormContent').reset();
            });
        }

        // Получаем ссылку на кнопку отправки формы создания привилегий
        const submitButton = document.getElementById('submitCreatePrivilegesForm');
        if (submitButton) {
            // Добавляем обработчик события на клик по кнопке отправки
            submitButton.addEventListener('click', function () {
                // Собираем данные из формы в объект FormData
                const formData = new FormData(document.getElementById('createPrivilegesFormContent'));
                // Создаем объект с данными для отправки на сервер
                const data = {
                    privilegeName: formData.get('privilegeName'),
                    privilegeID: formData.get('privilegeID'),
                    csrf_token: formData.get('csrf_token')
                };

                // Отправляем данные на сервер с помощью fetch
                fetch('back/authority_manager/create_privileges.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json;charset=UTF-8'
                    },
                    body: JSON.stringify(data) // Преобразуем данные в JSON
                })
                .then(response => response.json()) // Преобразуем ответ сервера в JSON
                .then(result => {
                    if (result.success) {
                        // Если операция успешна, показываем сообщение об успехе
                        showErrorMessage('success', 'Успех', 'Привилегия создана успешно!', 5000);
                        // Закрываем форму и сбрасываем её содержимое
                        closeForm('createPrivilegesForm');
                        document.getElementById('createPrivilegesFormContent').reset();
                    } else {
                        // Если операция не удалась, показываем сообщение об ошибке
                        showErrorMessage('error', 'Ошибка', result.message, 10000);
                    }
                })
                .catch(error => {
                    // В случае ошибки при отправке данных, логируем ошибку и показываем сообщение
                    console.error('Ошибка:', error);
                    showErrorMessage('error', 'Ошибка', 'Ошибка 0099: Произошла неизвестная ошибка.', 10000);
                });
            });
        }
    }
});

// Функция для открытия формы
function openForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        // Показываем форму и добавляем класс для анимации открытия
        form.style.display = "block";
        form.classList.remove("closing");
        form.classList.add("open");
    }
}

// Функция для закрытия формы
function closeForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        // Добавляем класс для анимации закрытия
        form.classList.add("closing");
        form.classList.remove("open");
        // Ожидаем окончания анимации и скрываем форму
        form.addEventListener("animationend", () => {
            if (form.classList.contains("closing")) {
                form.style.display = "none";
            }
        }, { once: true });
    }
}