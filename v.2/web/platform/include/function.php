<?php
    // Определение переменных
    if (!defined('LOGGER_PATH')) {
        define('LOGGER_PATH', '/var/log/slm/web/v2/web.log');
    }

    if (!defined('LOGOUT_PATH')) {
        define('LOGOUT_PATH', '/platform/logout.php');
    }
        
    // Функция для логирования
    function logger($level = 'INFO', $message = '') {
        $logFile = LOGGER_PATH;
        
        // Проверяем, существует ли файл логов, и создаем его, если нет
        if (!file_exists($logFile)) {
            touch($logFile); // Создаем файл, если он не существует
        }
        
        // Определяем инициатора (пользователя или "неизвестный")
        $initiator = isset($_SESSION['username']) ? $_SESSION['username'] : 'неизвестный';
        
        // Получаем ID сессии
        $sessionId = session_id();
        
        // Получаем текущий URL
        $url = $_SERVER['REQUEST_URI'];
        
        // Формируем строку для записи в лог
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] [Инициатор: $initiator] [ID сессии: $sessionId] [URL: $url] $message" . PHP_EOL;
        
        // Записываем сообщение в лог-файл
        file_put_contents($logFile, $logMessage, FILE_APPEND);
        
        return true; // Возвращаем успешное выполнение
    }

    // Функция для запуска сессии, если она еще не запущена
    function startSessionIfNotStarted() {
        if (session_status() === PHP_SESSION_DISABLED) {
            logger("ERROR", "Сессии отключены. Невозможно запустить сессию.");
            throw new Exception("Сессии отключены.");
        }
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
            logger("INFO", "Сессия успешно запущена. ID сессии: " . session_id());
        } else {
            logger("INFO", "Сессия уже активна. ID сессии: " . session_id());
        }
    }

    // Функция для проверки авторизации пользователя
    function checkAuth() {
        // Проверка наличия имени пользователя в сессии
        if (!isset($_SESSION['username'])) {
            logger("INFO", "Пользователь не авторизован: отсутствует username в сессии.");
            header("Location: " . LOGOUT_PATH);
            exit();
        }

        // Проверка наличия cookie с session_id
        if (!isset($_COOKIE['session_id'])) {
            logger("INFO", "Пользователь не авторизован: отсутствует session_id в cookie.");
            header("Location: " . LOGOUT_PATH);
            exit();
        }

        // Проверка совпадения session_id из cookie и текущей сессии
        if ($_COOKIE['session_id'] !== session_id()) {
            logger("WARNING", "Пользователь не авторизован: session_id из cookie не совпадает с текущей сессией.");
            header("Location: " . LOGOUT_PATH);
            exit();
        }

        // Проверки пройдены
        logger("INFO", "Пользователь авторизован: username = " . $_SESSION['username'] . ", session_id = " . session_id());
    }
?>