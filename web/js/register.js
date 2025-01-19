document.addEventListener("DOMContentLoaded", function () {
    console.log("Скрипт register.js загружен и выполняется.");

    const form = document.getElementById("registrationForm");

    if (!form) {
        console.error("Форма с ID 'registrationForm' не найдена.");
        return;
    }

    form.addEventListener("submit", function (event) {
        event.preventDefault(); // Отменяем стандартную отправку формы
        console.log("Форма отправлена.");

        const formData = new FormData(form);

        fetch(form.action, {
            method: "POST",
            body: formData,
            headers: {
                "Accept": "application/json"
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error("Ошибка сети или сервера.");
            }
            return response.json();
        })
        .then(data => {
            console.log("Ответ от сервера:", data);

            if (data.status === "success") {
                // Очищаем ошибки
                clearErrors();
                // Показываем уведомление об успехе
                showNotification(data.message, "success");
                // Очищаем форму
                form.reset();
            } else if (data.status === "error") {
                // Очищаем предыдущие ошибки
                clearErrors();
                // Показываем ошибки для каждого поля
                if (data.errors) {
                    for (const field in data.errors) {
                        const errorElement = document.getElementById(`${field}-error`);
                        if (errorElement) {
                            errorElement.textContent = data.errors[field];
                        }
                    }
                }
                // Показываем общее сообщение об ошибке
                showNotification(data.message, "error");
            }
        })
        .catch(error => {
            console.error("Ошибка при отправке запроса:", error);
            showNotification("Ошибка при отправке запроса.", "error");
        });
    });

    function clearErrors() {
        // Очищаем все сообщения об ошибках
        const errorElements = document.querySelectorAll(".error-message");
        errorElements.forEach(element => {
            element.textContent = "";
        });
    }

    function showNotification(message, type) {
        const notification = document.getElementById("notification");
        if (!notification) {
            console.error("Элемент с ID 'notification' не найден.");
            return;
        }
        notification.textContent = message;
        notification.className = type; // Добавляем класс для стилизации
        notification.style.display = "block";

        // Скрыть уведомление через 5 секунд
        setTimeout(() => {
            notification.style.display = "none";
        }, 5000);
    }
});