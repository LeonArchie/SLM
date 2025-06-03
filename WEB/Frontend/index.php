<?php
    // Инициализация вызвываемых функции
    $file_path = 'platform/include/function.php';
    if (!file_exists($file_path)) {
        // Если не существует, переходим 503.php
        header("Location: err/50x.html");
        exit();
    }
    require_once $file_path;

    $file_path = 'platform/include/binding/check_auth.php';
    if (!file_exists($file_path)) {
        // Если не существует, переходим 503.php
        header("Location: err/50x.html");
        exit();
    }
    require_once $file_path;

    logger("INFO", "index успешно инициализирован");

    //Инициализация проверки или запуска сессии
    startSessionIfNotStarted();

    // Основная логика
    if (checkAuth()) {
        logger("INFO", "Авторизация успешна, перенаправление на dashboard");
        header("Location: platform/dashboard.php");
    } else {
        logger("WARNING", "Авторизация не пройдена, перенаправление на logout");
        header("Location: platform/login.php");
    }
    exit();
?>

