<?php
    // Определение переменных
    if (!defined('LOGGER_PATH')) {
        define('LOGGER_PATH', '/var/log/slm/web.log');
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

    function checkPrivilege($privileges_page) {
        // Логируем начало выполнения функции
        logger("INFO", "Вызов checkPrivilege для привилегии: {$privileges_page}");
        
        // Проверяем наличие необходимых данных в сессии
        if (!isset($_SESSION['access_token']) || !isset($_SESSION['userid'])) {
            $errorMsg = "Отсутствует access_token или userid в сессии";
            logger("ERROR", $errorMsg);
            return false;
        }
    
        // Автоматически определяем базовый URL с портом 5000
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        
        // Удаляем стандартный порт если он есть в HTTP_HOST
        $host = str_replace([':80',':443'], '', $host);
        
        // Формируем URL с портом 5000
        $api_url = "{$protocol}://{$host}:5000/privileges/check-privilege";
        logger("DEBUG", "Сформирован URL API: {$api_url}");
    
        // Подготавливаем данные для запроса
        $post_data = [
            'access_token' => $_SESSION['access_token'],
            'privileges_id' => $privileges_page,
            'userid' => $_SESSION['userid']
        ];
        logger("DEBUG", "Данные запроса: " . json_encode($post_data));
    
        // Инициализируем cURL
        $ch = curl_init();
        
        // Настраиваем параметры запроса
        curl_setopt_array($ch, [
            CURLOPT_URL => $api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($post_data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 5
        ]);
    
        // Выполняем запрос
        logger("INFO", "Отправка запроса к API проверки привилегий");
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $errorMsg = "cURL Error: " . curl_error($ch);
            logger("ERROR", $errorMsg);
            curl_close($ch);
            return false;
        }
        
        curl_close($ch);
    
        // Обрабатываем ответ
        logger("DEBUG", "Получен ответ от API. HTTP код: {$http_code}, Ответ: {$response}");
        
        if ($http_code === 200) {
            $result = json_decode($response, true);
            $has_privilege = $result['has_privilege'] ?? false;
            logger("INFO", "Результат проверки привилегии: " . ($has_privilege ? "Доступ разрешен" : "Доступ запрещен"));
            return $has_privilege;
        }
    
        $errorMsg = "API request failed. HTTP code: {$http_code}, Response: {$response}";
        logger("ERROR", $errorMsg);
        return false;
    }

    logger("INFO", "function.php инициализирован");
?>