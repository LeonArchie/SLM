// Очередь уведомлений
const notificationQueue = [];
// Флаг, указывающий, показывается ли в данный момент уведомление
let isNotificationShowing = false;

function showErrorMessage(...args) {
    // Добавляем уведомление в очередь
    notificationQueue.push(args);

    // Если уведомление уже показывается, выходим
    if (isNotificationShowing) {
        return;
    }

    // Показываем следующее уведомление из очереди
    showNextNotification();
}

/**
 * Показывает следующее уведомление из очереди.
 */
function showNextNotification() {
    // Если очередь пуста, выходим
    if (notificationQueue.length === 0) {
        isNotificationShowing = false;
        return;
    }

    // Устанавливаем флаг, что уведомление показывается
    isNotificationShowing = true;

    // Получаем параметры уведомления из очереди
    const args = notificationQueue.shift();

    // Допустимые типы уведомлений
    const validTypes = ['success', 'error', 'warning'];
    // Параметры по умолчанию
    let type = 'warning'; // Тип уведомления по умолчанию
    let title = 'Внимание'; // Заголовок по умолчанию
    let message = ''; // Сообщение
    let duration = 7000; // Время показа по умолчанию

    // Обработка аргументов
    if (args.length === 1) {
        // Если передан только один аргумент, считаем его сообщением
        message = String(args[0]); // Преобразуем в строку
    } else if (args.length >= 2) {
        // Если передано больше одного аргумента, распределяем их по параметрам
        type = validTypes.includes(args[0]) ? args[0] : type; // Проверяем тип уведомления
        title = String(args[1] || title); // Преобразуем в строку
        message = String(args[2] || ''); // Преобразуем в строку
        duration = Number(args[3]) || duration; // Преобразуем в число
    }

    // Получаем элементы DOM
    const errorWindow = document.getElementById('error-window');
    const progressBar = document.getElementById('progress-bar');
    const errorMessageElement = document.getElementById('error-message');
    const errorTitleElement = document.getElementById('error-title');
    const closeButton = document.getElementById('close-button');

    // Проверяем наличие элементов
    if (!errorWindow || !progressBar || !errorMessageElement || !errorTitleElement || !closeButton) {
        console.error('Ошибка: Один или несколько элементов не найдены.');
        return;
    }

    // Настройка содержимого уведомления
    errorTitleElement.textContent = title; // Устанавливаем заголовок
    errorMessageElement.textContent = message; // Устанавливаем сообщение

    // Добавляем класс типа уведомления
    errorWindow.className = `error-window show ${type}`;

    // Показываем окно уведомления
    errorWindow.style.display = 'flex';

    // Время показа уведомления
    let timeLeft = duration;

    // Обновляем полосу прогресса каждые 100 мс
    const interval = setInterval(() => {
        const progressWidth = (timeLeft / duration) * 100; // Вычисляем ширину полосы
        progressBar.style.width = `${progressWidth}%`; // Устанавливаем ширину
        timeLeft -= 100; // Уменьшаем оставшееся время

        // Если время истекло, скрываем уведомление
        if (timeLeft <= 0) {
            clearInterval(interval); // Останавливаем интервал
            hideNotification(); // Скрываем уведомление
        }
    }, 100);

    // Обработчик закрытия уведомления по клику на кнопку
    function handleCloseButtonClick() {
        clearInterval(interval); // Останавливаем интервал
        hideNotification(); // Скрываем уведомление
        closeButton.removeEventListener('click', handleCloseButtonClick); // Удаляем обработчик
    }

    // Добавляем обработчик события на кнопку закрытия
    closeButton.addEventListener('click', handleCloseButtonClick);
}

/**
 * Скрывает текущее уведомление и показывает следующее из очереди.
 */
function hideNotification() {
    // Получаем элементы DOM
    const errorWindow = document.getElementById('error-window');
    const progressBar = document.getElementById('progress-bar');

    // Если элементы найдены, скрываем уведомление
    if (errorWindow && progressBar) {
        errorWindow.style.display = 'none'; // Скрываем окно
        progressBar.style.width = '0%'; // Сбрасываем полосу прогресса
        errorWindow.className = 'error-window'; // Сбрасываем классы
    }

    // Показываем следующее уведомление из очереди
    showNextNotification();
}

// Инициализация при загрузке страницы
document.addEventListener("DOMContentLoaded", function () {
    // Получаем глобальную переменную с сообщением об ошибке
    const errorMessage = window.errorMessage || "";

    // Если сообщение не пустое, показываем уведомление
    if (typeof errorMessage === "string" && errorMessage.trim() !== "") {
        showErrorMessage('warning', 'Внимание', errorMessage, 10000);
    } else {
        // Иначе скрываем уведомление
        hideNotification();
    }
});