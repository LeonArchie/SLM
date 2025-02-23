<?php
    require_once 'include/init.php';
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