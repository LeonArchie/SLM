<?php
    // Блок переменных
    define('LOGOUT_PATH', '/include/logout.php');
    define('LOGGER_PATH', '/var/log/slm/web/web.log');
    define('CONFIG_PATH', __DIR__ . '/../config/config.json');

    // Функция для запуска сессии, если она еще не запущена
    function startSessionIfNotStarted() {
        logger("INFO", "Попытка запуска сессии.");

        if (session_status() === PHP_SESSION_DISABLED) {
            logger("ERROR", "Сессии отключены. Невозможно запустить сессию.");
            throw new Exception("Сессии отключены.");
        }
    
        if (session_status() === PHP_SESSION_NONE) {
            logger("INFO", "Сессия не активна. Запуск сессии...");
            session_start();
            logger("INFO", "Сессия успешно запущена. ID сессии: " . session_id());
        } else {
            logger("INFO", "Сессия уже активна. ID сессии: " . session_id());
        }
    }

    // Функция для проверки авторизации пользователя
    function checkAuth() {
        logger("INFO", "Начало проверки авторизации пользователя.");

        // Проверка наличия имени пользователя в сессии
        if (!isset($_SESSION['username'])) {
            logger("ERROR", "Пользователь не авторизован: отсутствует username в сессии.");
            header("Location: " . LOGOUT_PATH);
            exit();
        }

        // Проверка наличия cookie с session_id
        if (!isset($_COOKIE['session_id'])) {
            logger("ERROR", "Пользователь не авторизован: отсутствует session_id в cookie.");
            header("Location: " . LOGOUT_PATH);
            exit();
        }

        // Проверка совпадения session_id из cookie и текущей сессии
        if ($_COOKIE['session_id'] !== session_id()) {
            logger("ERROR", "Пользователь не авторизован: session_id из cookie не совпадает с текущей сессией.");
            logger("DEBUG", "session_id из cookie: " . $_COOKIE['session_id']);
            logger("DEBUG", "session_id из сессии: " . session_id());
            header("Location: " . LOGOUT_PATH);
            exit();
        }

        // Если все проверки пройдены
        logger("INFO", "Пользователь авторизован: username = " . $_SESSION['username'] . ", session_id = " . session_id());
    }
    
    // Функция для генерации CSRF-токена
    function csrf_token() {
        logger("INFO", "Проверка наличия CSRF-токена.");

        if (empty($_SESSION['csrf_token'])) {
            // Генерация нового CSRF-токена
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            logger("INFO", "Сгенерирован новый CSRF-токен: " . $_SESSION['csrf_token']);
            return $_SESSION['csrf_token'];
        } else {
            // Возвращаем существующий токен
            logger("INFO", "Используется существующий CSRF-токен: " . $_SESSION['csrf_token']);
            return $_SESSION['csrf_token'];
        }
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
    
        // Формируем строку для записи в лог
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] [Инициатор: $initiator] [ID сессии: $sessionId] $message" . PHP_EOL;
    
        // Записываем сообщение в лог-файл
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    
        return true; // Возвращаем успешное выполнение
    }

    // Функция для генерации GUID
    function generateGUID() {
        logger("INFO", "Начало генерации GUID.");
    
        if (function_exists('com_create_guid') === true) {
            // Логирование использования встроенной функции com_create_guid
            logger("INFO", "Используется встроенная функция com_create_guid для генерации GUID.");
            $guid = trim(com_create_guid(), '{}');
            logger("INFO", "Сгенерирован GUID с использованием com_create_guid: " . $guid);
            return $guid;
        }
    
        // Логирование начала ручной генерации GUID
        logger("INFO", "Встроенная функция com_create_guid недоступна. Начало ручной генерации GUID.");
    
        // Генерация 16 байт случайных данных
        $data = random_bytes(16);
        logger("INFO", "Сгенерировано 16 байт случайных данных для GUID.");
    
        // Установка версии (4) и битов (10)
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Версия 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Биты 10
        logger("INFO", "Установлена версия и биты для GUID.");
    
        // Форматирование GUID в стандартный вид
        $guid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        logger("INFO", "Сгенерирован GUID вручную: " . $guid);
    
        return $guid;
    }
?>