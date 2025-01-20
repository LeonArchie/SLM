<?php
    // Блок переменных
    define('LOGOUT_PATH', '/include/logout.php');
    define('LOGGER_PATH', '/var/app.log');




    //Проверка запуска сесии
    function startSessionIfNotStarted() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    // Проверка авторизован ли пользователь
    function checkAuth() {
        startSessionIfNotStarted();
        if (!isset($_SESSION['username']) || !isset($_COOKIE['session_id']) || $_COOKIE['session_id'] !== session_id()) {
            header("Location: " . LOGOUT_PATH);
            exit();
        }
    }
    
    // Генерация CSRF-токена, если он еще не создан
    function csrf_token() {
        startSessionIfNotStarted();
	    if (empty($_SESSION['csrf_token'])) {
    	    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            return $_SESSION['csrf_token'];
	    }
    }
    function logger() {
        $logFile = LOGGER_PATH;
        if (!file_exists($logFile)) {
            touch($logFile); // Создаем файл, если он не существует
        }
        ini_set('error_log', $logFile); // Указываем путь к файлу логов
        ini_set('log_errors', 1); // Включаем запись ошибок в лог
        ini_set('display_errors', 0); // Отключаем вывод ошибок на экран (для production)
        return true; // Возвращаем успешное выполнение
    }
?>