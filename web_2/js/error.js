// Функция для показа ошибки
function showErrorMessage(message) {
    const errorWindow = document.getElementById('error-window');
    const progressBar = document.getElementById('progress-bar');
    const errorMessageElement = document.getElementById('error-message');

    if (!errorWindow || !progressBar || !errorMessageElement) {
        console.error('Ошибка: Элементы error-window, progress-bar или error-message не найдены.');
        return;
    }

    // Устанавливаем текст ошибки
    errorMessageElement.textContent = message;

    // Показываем окно ошибки
    errorWindow.style.display = 'flex';

    // Время показа в миллисекундах
    const totalTime = 10000;
    let timeLeft = totalTime;

    // Обновляем ширину полосы прогресса каждые 100мс
    const interval = setInterval(function () {
        const progressWidth = (timeLeft / totalTime) * 100;
        progressBar.style.width = `${progressWidth}%`;
        timeLeft -= 100;

        if (timeLeft <= 0) {
            clearInterval(interval);
            errorWindow.style.display = 'none'; // Скрываем окно после истечения времени
            progressBar.style.width = '0%'; // Сбрасываем ширину полосы прогресса
        }
    }, 100);
}

// При загрузке страницы проверяем глобальную переменную window.errorMessage
document.addEventListener("DOMContentLoaded", function () {
    const errorMessage = window.errorMessage || ""; // Получаем глобальную переменную
    const errorWindow = document.getElementById('error-window');
    const progressBar = document.getElementById('progress-bar');

    if (typeof errorMessage === "string" && errorMessage.trim() !== "") {
        showErrorMessage(errorMessage); // Вызываем функцию для показа ошибки
    } else {
        // Если сообщение пустое, скрываем окно и сбрасываем полосу прогресса
        if (errorWindow) {
            errorWindow.style.display = 'none';
        }
        if (progressBar) {
            progressBar.style.width = '0%'; // Сбрасываем ширину полосы прогресса
        }
    }
});