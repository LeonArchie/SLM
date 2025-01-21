document.addEventListener("DOMContentLoaded", function () {
    // Функция для логирования с уровнями
    function log(level, message) {
        const timestamp = new Date().toISOString();
        console.log(`[${timestamp}] [${level}] ${message}`);
    }

    log("INFO", "Скрипт input_err.js загружен и готов к работе.");

    const form = document.querySelector(".authorization form");
    const inputs = document.querySelectorAll(".authorization input[required]");

    if (!form || inputs.length === 0) {
        log("ERROR", "Форма или обязательные поля не найдены.");
        return;
    }

    log("INFO", "Форма и обязательные поля найдены.");

    // Проверка при отправке формы
    form.addEventListener("submit", function (event) {
        log("INFO", "Начало проверки формы перед отправкой.");
        let isFormValid = true;

        inputs.forEach(input => {
            if (!input.value.trim()) {
                log("DEBUG", `Поле ${input.name || input.id} не заполнено.`);
                input.classList.add("invalid"); // Добавляем класс для невалидного поля
                isFormValid = false;
            } else {
                log("DEBUG", `Поле ${input.name || input.id} заполнено корректно.`);
                input.classList.remove("invalid"); // Убираем класс, если поле валидно
            }
        });

        if (!isFormValid) {
            log("ERROR", "Форма содержит ошибки. Отправка отменена.");
            event.preventDefault(); // Останавливаем отправку формы, если есть ошибки
        } else {
            log("INFO", "Форма валидна. Отправка разрешена.");
        }
    });
});