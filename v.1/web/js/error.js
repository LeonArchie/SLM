// Функция для показа уведомления
function showErrorMessage(...args) {
    let type = 'warning'; // Тип уведомления по умолчанию
    let title = 'Внимание'; // Заголовок по умолчанию
    let message = ''; // Сообщение
    let duration = 7000; // Время показа по умолчанию

    // Определяем параметры в зависимости от количества переданных аргументов
    if (args.length === 1) {
        // Если передано только одно значение, считаем его сообщением
        message = args[0];
    } else if (args.length >= 2) {
        // Если передано больше одного значения, распределяем их по параметрам
        type = args[0] || type; // Тип уведомления
        title = args[1] || title; // Заголовок
        message = args[2] || ''; // Сообщение
        duration = args[3] || duration; // Время показа
    }

    const errorWindow = document.getElementById('error-window');
    const progressBar = document.getElementById('progress-bar');
    const errorMessageElement = document.getElementById('error-message');
    const errorTitleElement = document.getElementById('error-title');

    if (!errorWindow || !progressBar || !errorMessageElement || !errorTitleElement) {
        console.error('Ошибка: Один или несколько элементов не найдены.');
        return;
    }

    // Настройка содержимого окна
    errorTitleElement.textContent = title;
    errorMessageElement.textContent = message;

    // Добавляем класс типа уведомления (success, error, warning)
    errorWindow.className = 'error-window show ' + type;

    // Показываем окно
    errorWindow.style.display = 'flex';

    // Время показа в миллисекундах
    let timeLeft = duration;

    // Обновляем ширину полосы прогресса каждые 100мс
    const interval = setInterval(function () {
        const progressWidth = (timeLeft / duration) * 100;
        progressBar.style.width = `${progressWidth}%`;
        timeLeft -= 100;

        if (timeLeft <= 0) {
            clearInterval(interval);
            hideNotification(); // Скрываем окно после истечения времени
        }
    }, 100);

    // Закрытие окна по клику на кнопку
    const closeButton = document.getElementById('close-button');
    closeButton.addEventListener('click', function () {
        clearInterval(interval); // Останавливаем таймер
        hideNotification(); // Скрываем окно
    });
}

// Функция для скрытия уведомления
function hideNotification() {
    const errorWindow = document.getElementById('error-window');
    const progressBar = document.getElementById('progress-bar');

    if (errorWindow && progressBar) {
        errorWindow.style.display = 'none'; // Скрываем окно
        progressBar.style.width = '0%'; // Сбрасываем ширину полосы прогресса
        errorWindow.className = 'error-window'; // Сбрасываем класс типа уведомления
    }
}

// При загрузке страницы проверяем глобальную переменную window.errorMessage
document.addEventListener("DOMContentLoaded", function () {
    const errorMessage = window.errorMessage || ""; // Получаем глобальную переменную

    if (typeof errorMessage === "string" && errorMessage.trim() !== "") {
        // Если есть сообщение об ошибке, показываем уведомление типа "error"
        showErrorMessage('warning', 'Внимание', errorMessage, 10000);
    } else {
        // Если сообщение пустое, скрываем окно и сбрасываем полосу прогресса
        const errorWindow = document.getElementById('error-window');
        const progressBar = document.getElementById('progress-bar');

        if (errorWindow) {
            errorWindow.style.display = 'none';
        }
        if (progressBar) {
            progressBar.style.width = '0%';
        }
    }
});