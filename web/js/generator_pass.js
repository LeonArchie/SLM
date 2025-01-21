document.addEventListener("DOMContentLoaded", function () {
    // Функция для логирования с уровнями
    function log(level, message) {
        const timestamp = new Date().toISOString();
        console.log(`[${timestamp}] [${level}] ${message}`);
    }

    log("INFO", "Скрипт generator_pass.js загружен и готов к работе.");

    const generateButton = document.getElementById("generate-password");
    const passwordField = document.getElementById("password");

    if (!generateButton || !passwordField) {
        log("ERROR", "Элементы для генерации пароля не найдены.");
        return;
    }

    log("INFO", "Элементы для генерации пароля найдены.");

    generateButton.addEventListener("click", function () {
        log("INFO", "Нажата кнопка генерации пароля.");
        const randomPassword = generateRandomPassword(10);
        passwordField.value = randomPassword;
        log("DEBUG", `Сгенерирован пароль: ${randomPassword}`);
    });

    function generateRandomPassword(length) {
        log("DEBUG", "Начало генерации случайного пароля.");
        const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        let password = "";

        for (let i = 0; i < length; i++) {
            const randomIndex = Math.floor(Math.random() * charset.length);
            password += charset[randomIndex];
        }

        log("DEBUG", "Пароль успешно сгенерирован.");
        return password;
    }
});