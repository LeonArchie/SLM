<?php
// Подключение функции логирования
require_once __DIR__ . '/../include/function.php';

// Логирование начала выполнения скрипта
logger("INFO", "Начало выполнения скрипта logout.php.");

// Запуск сессии
session_start();
//logger("INFO", "Сессия успешно запущена. ID сессии: " . session_id());

// Логирование информации о пользователе перед выходом
if (isset($_SESSION['username'])) {
    logger("INFO", "Пользователь " . $_SESSION['username'] . " начал процесс выхода из системы.");
} else {
    logger("WARNING", "Попытка выхода из системы неавторизованным пользователем.");
}

// Удаление CSRF-токена из сессии
if (isset($_SESSION['csrf_token'])) {
    unset($_SESSION['csrf_token']);
    logger("INFO", "CSRF-токен успешно удален из сессии.");
} else {
    logger("WARNING", "CSRF-токен не найден в сессии.");
}

// Удаление CSRF-токена из куки (если он там есть)
if (isset($_COOKIE['csrf_token'])) {
    setcookie("csrf_token", "", time() - 3600, "/");
    logger("INFO", "CSRF-токен успешно удален из куки.");
} else {
    logger("WARNING", "CSRF-токен не найден в куки.");
}

// Удаление данных сессии
session_unset();
logger("INFO", "Данные сессии успешно удалены.");

// Уничтожение сессии
if (session_destroy()) {
    logger("INFO", "Сессия успешно уничтожена.");
} else {
    logger("ERROR", "Не удалось уничтожить сессию.");
}

// Удаление куки session_id
if (isset($_COOKIE['session_id'])) {
    if (setcookie("session_id", "", time() - 3600, "/")) {
        logger("INFO", "Кука session_id успешно удалена.");
    } else {
        logger("ERROR", "Не удалось удалить куку session_id.");
    }
} else {
    logger("WARNING", "Кука session_id не найдена.");
}

// Логирование завершения процесса выхода
logger("INFO", "Процесс выхода из системы завершен.");

// Логирование перед перенаправлением
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'неизвестный IP';
logger("INFO", "Перенаправление на страницу авторизации. IP пользователя: " . $ipAddress);

// Перенаправление на страницу авторизации
header("Location: /../login.php");
exit();
?>