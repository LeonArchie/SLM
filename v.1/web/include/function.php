<?php
    // Определяем корень веб-сервера
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
    }

    // Блок переменных
    if (!defined('LOGOUT_PATH')) {
        define('LOGOUT_PATH', '/back/logout.php');
    }

    if (!defined('LOGGER_PATH')) {
        define('LOGGER_PATH', '/var/log/slm/web/web.log');
    }

    if (!defined('AUDIT_PATH')) {
        define('AUDIT_PATH', '/var/log/slm/web/audit.log');
    }

    if (!defined('CONFIG_PATH')) {
        define('CONFIG_PATH', ROOT_PATH . '/config/config.json');
    }

    if (!defined('CONFIG_MENU')) {
        define('CONFIG_MENU', ROOT_PATH . '/config/modules.json');
    }

    if (!defined('TEMPLATE')) {
        define('TEMPLATE', ROOT_PATH . '/config/template.json');
    }

    if (!defined('FORBIDDEN')) {
        define('FORBIDDEN', '/err/403.html');
    }

    if (!defined('NOT_FOUND')) {
        define('NOT_FOUND', '/err/404.html');
    }

    if (!defined('SERVER_ERROR')) {
        define('SERVER_ERROR', '/err/50x.html');
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
    
    // Функция для генерации CSRF-токена
    function csrf_token() {

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
        
        // Получаем текущий URL
        $url = $_SERVER['REQUEST_URI'];
        
        // Формируем строку для записи в лог
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] [Инициатор: $initiator] [ID сессии: $sessionId] [URL: $url] $message" . PHP_EOL;
        
        // Записываем сообщение в лог-файл
        file_put_contents($logFile, $logMessage, FILE_APPEND);
        
        return true; // Возвращаем успешное выполнение
    }

        // Функция для аудита
        function audit($level = 'INFO', $message = '') {
            $AuditFile = AUDIT_PATH;
            
            // Проверяем, существует ли файл логов, и создаем его, если нет
            if (!file_exists($AuditFile)) {
                touch($AuditFile); // Создаем файл, если он не существует
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
            file_put_contents($AuditFile, $logMessage, FILE_APPEND);
            
            return true; // Возвращаем успешное выполнение
        }

    // Функция для генерации GUID
    function generateGUID() {
        // Генерация 16 байт случайных данных
        $data = random_bytes(16);
        // Установка версии (4) и битов (10)
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Версия 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Биты 10
        
        // Форматирование GUID в стандартный вид
        $guid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        logger("INFO", "Сгенерирован GUID: " . $guid);   
        return $guid;
    }

    function FROD($guid) {
        // Проверяем, определена ли константа CONFIG_PATH
        if (!defined('CONFIG_PATH')) {
            logger("ERROR", "Переменная CONFIG_PATH не определена.");
            header("Location: " . SERVER_ERROR);
            exit();
        }
    
        // Проверяем существование файла config.json
        if (!file_exists(CONFIG_PATH)) {
            logger("ERROR", "Файл config.json не найден: " . CONFIG_PATH);
            header("Location: " . SERVER_ERROR);
            exit();
        }
    
        // Чтение файла config.json
        $configJson = file_get_contents(CONFIG_PATH);
        if ($configJson === false) {
            logger("ERROR", "Ошибка при чтении файла config.json: " . CONFIG_PATH);
            header("Location: " . SERVER_ERROR);
            exit();
        }
    
        // Декодирование JSON
        $configData = json_decode($configJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            logger("ERROR", "Ошибка при декодировании config.json: " . json_last_error_msg());
            header("Location: " . SERVER_ERROR);
            exit();
        }
    
        // Проверяем статус FROD
        $frodEnabled = isset($configData['web']['frod']) && 
                       (strtolower((string)$configData['web']['frod']) === 'true' || 
                        $configData['web']['frod'] === true);
    
        // Если FROD выключен, завершаем проверку
        if (!$frodEnabled) {
            logger("WARNING", "FROD выключен.");
            return;
        }
    
        // Получаем значение id_privileges_frod_ignore из конфига
        $frodIgnoreId = isset($configData['web']['id_privileges_frod_ignore']) ? $configData['web']['id_privileges_frod_ignore'] : null;
    
        // Подключаемся к базе данных
        try {
            $pdo = connectToDatabase();
            if (!$pdo instanceof PDO) {
                throw new Exception("Не удалось получить объект PDO.");
            }
        } catch (Exception $e) {
            logger("ERROR", "Ошибка подключения к базе данных: " . $e->getMessage());
            header("Location: " . SERVER_ERROR);
            exit();
        }
    
        // Проверяем права пользователя
        try {
            // Получаем userid из сессии
            $userid = $_SESSION['userid'] ?? null;
            if (!$userid) {
                logger("ERROR", "User ID не найден в сессии.");
                header("Location: " . FORBIDDEN);
                exit();
            }
    
            // Выполняем запрос к таблице privileges для получения id_privileges
            $stmt = $pdo->prepare("SELECT id_privileges FROM privileges WHERE userid = :userid");
            $stmt->execute([':userid' => $userid]);
            $privileges = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
            // Если id_privileges_frod_ignore задан в конфиге и есть в privileges
            if ($frodIgnoreId !== null && in_array($frodIgnoreId, $privileges, true)) {
                logger("INFO", "Для пользователя $userid включено игнорирование проверки ФРОД");
                return;
            }
    
            // Проверяем, есть ли в privileges переданный GUID
            if (in_array($guid, $privileges, true)) {
                logger("INFO", "Пользователь $userid имеет права доступа к GUID: $guid.");
                return;
            }
    
            // Если права не найдены
            logger("ERROR", "У пользователя $userid нет прав доступа к GUID: $guid.");
            header("Location: " . FORBIDDEN);
            exit();
        } catch (Exception $e) {
            logger("ERROR", "Ошибка при проверке прав доступа: " . $e->getMessage());
            header("Location: " . SERVER_ERROR);
            exit();
        }
    }
    
    function connectToDatabase() {
        static $pdo = null; // переменная для хранения соединения
        if ($pdo !== null) {
            //logger("INFO", "Используется существующее соединение.");
            return $pdo;
        }        
        try {       
            // Путь к конфигурационному файлу
            $configFile = CONFIG_PATH;
            // Чтение конфигурации
            $configContent = file_get_contents($configFile);
                if ($configContent === false) {
                    logger("ERROR", "Не удалось прочитать файл конфигурации: " . $configFile);
                    throw new Exception("Не удалось прочитать файл конфигурации: " . $configFile);
                }
        
                $config = json_decode($configContent, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    logger("ERROR", "Ошибка при декодировании JSON: " . json_last_error_msg());
                    throw new Exception("Ошибка при декодировании JSON: " . json_last_error_msg());
                }
        
                // Проверка наличия раздела 'db' в конфигурации
                if (!isset($config['db'])) {
                    logger("ERROR", "Раздел 'db' отсутствует в конфигурационном файле.");
                    throw new Exception("Раздел 'db' отсутствует в конфигурационном файле.");
                }
        
                // Извлечение параметров подключения к базе данных из конфигурации
                $dbConfig = $config['db'];
        
                // Проверка наличия всех необходимых параметров подключения
                $requiredKeys = ['host', 'port', 'name', 'user', 'password'];
                foreach ($requiredKeys as $key) {
                    if (!isset($dbConfig[$key])) {
                        logger("ERROR", "Не хватает параметра '$key' в конфигурации базы данных.");
                        throw new Exception("Не хватает параметра '$key' в конфигурации базы данных.");
                    }
                }
        
                // Извлечение параметров подключения
                $host = $dbConfig['host'];
                $port = $dbConfig['port'];
                $dbname = $dbConfig['name'];
                $user = $dbConfig['user'];
                $password = $dbConfig['password'];
        
                // Формирование строки подключения к PostgreSQL
                $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;options='--client_encoding=UTF8'";
        
                // Создание подключения к базе данных с использованием PDO
                $pdo = new PDO($dsn, $user, $password);
        
                // Настройка атрибутов PDO
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Исключения при ошибках
                $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // Ассоциативные массивы
                $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // Отключение эмуляции подготовленных запросов
                $pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false); // Сохранение числовых типов
                $pdo->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER); // Ключи в нижнем регистре
                $pdo->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_NATURAL); // Корректная обработка NULL
                $pdo->setAttribute(PDO::ATTR_TIMEOUT, 5); // Таймаут соединения
        
                // Дополнительные настройки PostgreSQL
                $pdo->exec("SET CLIENT_ENCODING TO 'UTF8'"); // Установка кодировки
                $pdo->exec("SET TIME ZONE 'Asia/Yekaterinburg'"); // Установка временной зоны Екатеринбург
                $pdo->exec("SET SEARCH_PATH TO public"); // Установка схемы public
              
                logger("INFO", "Подключение к базе данных успешно установлено. Атрибуты PDO и настройки PostgreSQL настроены.");
        
                return $pdo;
            } catch (Exception $e) {
                logger("ERROR", "Ошибка подключения к базе данных: " . $e->getMessage());
                header("Location: " . SERVER_ERROR);
                exit();
            }
        }

        //Инфо о успешном подключении 
        logger("INFO", "Функции платформы успешно подключены");
?>