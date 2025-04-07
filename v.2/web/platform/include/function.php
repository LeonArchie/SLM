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

    logger("INFO", "function.php инициализирован");
?>