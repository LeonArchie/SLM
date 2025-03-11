<?php
    // Инициализация вызвываемых функции
    $file_path = __DIR__ . 'include/function.php';
    if (!file_exists($file_path)) {
        // Если не существует, переходим 503.php
        header("Location: /err/50x.html");
        exit();
    }
    require_once $file_path;

    //Инициализация проверки или запуска сессии
    startSessionIfNotStarted();

    // Проверка авторизации
    checkAuth();

    header("Location: dashboard.php");
?>