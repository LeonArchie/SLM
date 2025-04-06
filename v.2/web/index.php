<?php
    // Инициализация вызвываемых функции
    $file_path = 'platform/include/function.php';
    if (!file_exists($file_path)) {
        // Если не существует, переходим 503.php
        header("Location: err/50x.html");
        exit();
    }
    require_once $file_path;

    logger("INFO", "index успешно инициализирован");

    //Инициализация проверки или запуска сессии
    startSessionIfNotStarted();

    // Проверка авторизации
    checkAuth();

    header("Location: platform/dashboard.php");
?>