<?php
    // Инициализация вызвываемых функции
    $file_path = 'include/init.php';
    if (!file_exists($file_path)) {
        // Если файл не существует, переходим на страницу 503.php
        header("Location: /err/50x.html");
        exit();
    }
    
    // Подключаем файл, так как он существует
    require_once $file_path;
    
    // Логирование начала выполнения скрипта
    logger("INFO", "Начало выполнения скрипта index.php.");
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