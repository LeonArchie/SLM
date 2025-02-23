<?php
    // Инициализация вызвываемых функции
        require_once 'function.php';
    //Инициализация проверки или запуска сессии
        startSessionIfNotStarted();
    // Проверка авторизации
        checkAuth();
    // Генерация CSRF-токена
        csrf_token();
?>