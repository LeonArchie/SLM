document.addEventListener("DOMContentLoaded", function () {
    // Функция для логирования с уровнями
    function log(level, message) {
        const timestamp = new Date().toISOString();
        console.log(`[${timestamp}] [${level}] ${message}`);
    }

    log("INFO", "Скрипт register.js загружен и готов к работе.");

    const form = document.getElementById("registrationForm");

    if (!form) {
        log("ERROR", "Форма с ID 'registrationForm' не найдена.");
        return;
    }

    log("INFO", "Форма регистрации найдена.");

    form.addEventListener("submit", function (event) {
        event.preventDefault(); // Отменяем стандартную отправку формы

        log("INFO", "Начало обработки формы регистрации.");

        const formData = new FormData(form);

        // Логирование данных формы перед отправкой
        log("DEBUG", "Данные формы перед отправкой:");
        for (let [key, value] of formData.entries()) {
            log("DEBUG", `Поле: ${key}, Значение: ${value}`);
        }

        log("INFO", "Отправка данных на сервер...");

        fetch(form.action, {
            method: "POST",
            body: formData,
            headers: {
                "Accept": "application/json"
            }
        })
        .then(response => {
            log("INFO", "Ответ от сервера получен. Статус: " + response.status);

            if (!response.ok) {
                log("ERROR", "Ошибка сети или сервера. Статус ответа: " + response.status);
                throw new Error("Ошибка сети или сервера.");
            }
            return response.json();
        })
        .then(data => {
            log("INFO", "Ответ от сервера: " + JSON.stringify(data));

            // Очищаем предыдущие ошибки и индикаторы
            log("DEBUG", "Очистка предыдущих ошибок и индикаторов.");
            clearErrors();
            clearIndicators();

            if (data.status === "success") {
                log("INFO", "Регистрация прошла успешно. Данные: " + JSON.stringify(data));
                // Показываем зелёные индикаторы для всех полей
                setIndicators("valid");
                // Показываем уведомление об успехе
                showNotification(data.message, "success");
                // Очищаем форму
                form.reset();
                log("INFO", "Форма успешно сброшена.");
            } else if (data.status === "error") {
                log("INFO", "Обнаружены ошибки: " + JSON.stringify(data.errors));
                // Показываем красные индикаторы для полей с ошибками
                if (data.errors) {
                    const errorSummary = document.getElementById("error-summary");

                    if (!errorSummary) {
                        log("ERROR", "Элемент с ID 'error-summary' не найден.");
                        return;
                    }

                    errorSummary.innerHTML = ""; // Очищаем предыдущие ошибки
                    log("DEBUG", "Очистка блока с ошибками.");

                    for (const field in data.errors) {
                        log("DEBUG", `Ошибка в поле ${field}: ${data.errors[field]}`);
                        const errorMessage = document.createElement("div");
                        errorMessage.textContent = data.errors[field];
                        errorSummary.appendChild(errorMessage);

                        const indicator = document.getElementById(`${field}-indicator`);
                        if (indicator) {
                            log("DEBUG", `Установка красного индикатора для поля ${field}.`);
                            indicator.classList.add("invalid");
                        } else {
                            log("ERROR", `Индикатор для поля ${field} не найден.`);
                        }
                    }

                    // Показываем блок с ошибками, если они есть
                    errorSummary.style.display = "block";
                    log("INFO", "Блок с ошибками отображен.");
                }
                // Показываем общее сообщение об ошибке
                showNotification(data.message, "error");
            }
        })
        .catch(error => {
            log("ERROR", "Ошибка при отправке запроса: " + error.message);
            showNotification("Ошибка при отправке запроса.", "error");
        });
    });

    function clearErrors() {
        log("DEBUG", "Очистка всех сообщений об ошибках.");
        const errorSummary = document.getElementById("error-summary");

        if (!errorSummary) {
            log("ERROR", "Элемент с ID 'error-summary' не найден.");
            return;
        }

        errorSummary.innerHTML = "";
        errorSummary.style.display = "none"; // Скрываем блок, если ошибок нет
        log("DEBUG", "Блок с ошибками очищен и скрыт.");
    }

    function clearIndicators() {
        log("DEBUG", "Очистка всех индикаторов.");
        const indicators = document.querySelectorAll(".validation-indicator");

        if (indicators.length === 0) {
            log("ERROR", "Индикаторы не найдены.");
            return;
        }

        indicators.forEach(indicator => {
            log("DEBUG", `Очистка индикатора: ${indicator.id}`);
            indicator.classList.remove("valid", "invalid");
        });
    }

    function setIndicators(status) {
        log("DEBUG", `Установка индикаторов: ${status}`);
        const indicators = document.querySelectorAll(".validation-indicator");

        if (indicators.length === 0) {
            log("ERROR", "Индикаторы не найдены.");
            return;
        }

        indicators.forEach(indicator => {
            log("DEBUG", `Установка индикатора ${indicator.id} в состояние: ${status}`);
            indicator.classList.add(status);
        });
    }

    function showNotification(message, type) {
        log("INFO", `Показ уведомления: ${message}, Тип: ${type}`);
        const notification = document.getElementById("notification");

        if (!notification) {
            log("ERROR", "Элемент с ID 'notification' не найден.");
            return;
        }

        notification.textContent = message;
        notification.className = type; // Добавляем класс для стилизации
        notification.style.display = "block";
        log("INFO", "Уведомление отображено.");

        // Скрыть уведомление через 5 секунд
        setTimeout(() => {
            log("DEBUG", "Скрытие уведомления.");
            notification.style.display = "none";
        }, 5000);
    }
});