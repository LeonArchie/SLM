<?php
require_once 'include/function.php';

// Логирование начала выполнения скрипта
logger("INFO", "Начало выполнения скрипта index.php.");

// Запуск сессии
startSessionIfNotStarted();
logger("INFO", "Сессия успешно запущена. ID сессии: " . session_id());

// Проверка, авторизован ли пользователь
if (isset($_SESSION['session_id']) && isset($_SESSION['username'])) {
    logger("INFO", "Пользователь авторизован. Username: " . $_SESSION['username']);
    logger("INFO", "Перенаправление на dashboard.php.");
    header("Location: dashboard.php");
    exit();
} else {
    logger("INFO", "Пользователь не авторизован.");
    logger("INFO", "Перенаправление на login.php.");
    header("Location: login.php");
    exit();
}
?>