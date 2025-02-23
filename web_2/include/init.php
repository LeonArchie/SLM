<?php
    // Инициализация вызвываемых функции
    $file_path = __DIR__ . '/function.php';

    if (!file_exists($file_path)) {
        // Если файл не существует, переходим на страницу 503.php
        header("Location: /err/50x.html");
        exit();
    }
    // Подключаем файл, так как он существует
    require_once $file_path;
    //Инициализация проверки или запуска сессии
        startSessionIfNotStarted();
    // Проверка авторизации
        checkAuth();
    // Генерация CSRF-токена
        csrf_token();
?>