document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("registrationForm");

    form.addEventListener("submit", function (event) {
        event.preventDefault(); // Отменяем стандартную отправку формы

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

            // Очищаем предыдущие ошибки и индикаторы
            clearErrors();
            clearIndicators();

            if (data.status === "success") {
                // Показываем зелёные индикаторы для всех полей
                setIndicators("valid");
                // Показываем уведомление об успехе
                showNotification(data.message, "success");
                // Очищаем форму
                form.reset();
            } else if (data.status === "error") {
                // Показываем красные индикаторы для полей с ошибками
                if (data.errors) {
                    for (const field in data.errors) {
                        const indicator = document.getElementById(`${field}-indicator`);
                        if (indicator) {
                            indicator.classList.add("invalid");
                        }
                        const errorElement = document.getElementById(`${field}-error`);
                        if (errorElement) {
                            errorElement.textContent = data.errors[field];
                        }
                    }
                }
                // Показываем общее сообщение об ошибке
                showNotification(data.message, "error");
                // Отображаем все ошибки под кнопкой
                showErrorSummary(data.errors);
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

    function clearIndicators() {
        // Очищаем все индикаторы
        const indicators = document.querySelectorAll(".validation-indicator");
        indicators.forEach(indicator => {
            indicator.classList.remove("valid", "invalid");
        });
    }

    function setIndicators(status) {
        // Устанавливаем индикаторы для всех полей
        const indicators = document.querySelectorAll(".validation-indicator");
        indicators.forEach(indicator => {
            indicator.classList.add(status);
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

    function showErrorSummary(errors) {
        const errorSummary = document.getElementById("error-summary");
        if (!errorSummary) {
            console.error("Элемент с ID 'error-summary' не найден.");
            return;
        }
        // Очищаем предыдущие ошибки
        errorSummary.innerHTML = "";
        // Добавляем новые ошибки
        if (errors) {
            for (const field in errors) {
                const errorMessage = document.createElement("div");
                errorMessage.textContent = errors[field];
                errorSummary.appendChild(errorMessage);
            }
        }
    }
});