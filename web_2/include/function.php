<?php
    // Блок переменных
        define('LOGOUT_PATH', '/../back/logout.php');
        define('LOGGER_PATH', '/var/log/slm/web/web.log');
        define('CONFIG_PATH', __DIR__ . '/../config/config.json');
        define('CONFIG_MENU', '/../config/modules.json');
        define('FORBIDDEN', '/../err/403.html');
        define('NOT_FOUND', '/../err/404.html');
        define('SERVER_ERROR', '/../err/50x.html');
        define('DB_CONNECT', '/../back/db_connect.php');
    
    //Отбрасываем построенные переменные в лог
    logger("INFO", "Начато подключение function.php");


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

    // Проверка на FROD - Нелегитимный доступ
        function frod($strGuid) {
            // Получаем текущий URL
            $currentUrl = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'UNKNOWN';
        
            // Проверяем, определена ли константа CONFIG_PATH
            if (!defined('CONFIG_PATH')) {
                logger("ERROR", "Переменная CONFIG_PATH не определена. [URL: $currentUrl]");
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
        
            // Проверяем наличие и значение ключа app.frod
            $frodEnabled = isset($configData['web']['frod']) && (strtolower((string)$configData['app']['frod']) === 'true' || $configData['app']['frod'] === true);
        
            // Если frod отключен или отсутствует, пропускаем выполнение функции
            if (!$frodEnabled) {
                logger("INFO", "Функция frod пропущена, так как app.frod отключен или отсутствует. [URL: $currentUrl]");
                return;
            }
        
            // Проверяем, есть ли текущий URL в списке frod_ignore
            $frodIgnore = isset($configData['web']['frod_ignore']) ? $configData['web']['frod_ignore'] : [];
            if (in_array($currentUrl, $frodIgnore, true)) {
                logger("INFO", "Проверка frod пропущена для URL: $currentUrl (находится в списке frod_ignore).");
                return; // Пропускаем проверку, если URL в списке игнорирования
            }
        
            // Подключение к базе данных через db_connect.php
            require_once 'DB_CONNECT'; // Выполняем скрипт db_connect.php
        
            try {
                // Предполагается, что после выполнения db_connect.php глобальная переменная $pdo создана
                if (!isset($pdo) || !$pdo instanceof PDO) {
                    throw new Exception("Объект PDO не был создан.");
                }
        
                // Выполнение запроса для получения roleid для роли 'Администратор'
                $stmt = $pdo->prepare("SELECT roleid FROM name_rol WHERE names_rol = :role_name");
                $stmt->execute([':role_name' => 'Администратор']);
                $adminRole = $stmt->fetchColumn();
        
                // Проверка наличия значения roleid в сессии
                if (isset($_SESSION['roleid']) && $_SESSION['roleid'] == $adminRole) {
                    logger("INFO", "Доступ разрешен для администратора. [URL: $currentUrl]");
                    return; // Завершаем функцию для администратора
                } else {
                    // Логируем, что пользователь не является администратором
                    logger("INFO", "Пользователь не является администратором. Продолжение проверки frod. [URL: $currentUrl]");
                }
            } catch (Exception $e) {
                logger("ERROR", "Ошибка при проверке прав администратора: " . $e->getMessage());
                header("Location: " . SERVER_ERROR);
                exit();
            }
        
            // Проверяем STR_GUID
            if (empty($strGuid)) {
                logger("ERROR", "STR_GUID не передан. [URL: $currentUrl]");
                header("Location: " . SERVER_ERROR);
                exit();
            }
        
            // Проверяем, определена ли переменная CONFIG_MENU
            if (!defined('CONFIG_MENU')) {
                logger("ERROR", "Переменная CONFIG_MENU не определена. [URL: $currentUrl]");
                header("Location: " . SERVER_ERROR);
                exit();
            }
        
            // Проверяем существование файла menu.json
            if (!file_exists(CONFIG_MENU)) {
                logger("ERROR", "Файл menu.json не найден: " . CONFIG_MENU);
                header("Location: " . SERVER_ERROR);
                exit();
            }
        
            // Чтение файла menu.json
            $menuJson = file_get_contents(CONFIG_MENU);
            if ($menuJson === false) {
                logger("ERROR", "Ошибка при чтении файла menu.json: " . CONFIG_MENU);
                header("Location: " . SERVER_ERROR);
                exit();
            }
        
            // Декодирование JSON
            $menuData = json_decode($menuJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                logger("ERROR", "Ошибка при декодировании menu.json: " . json_last_error_msg());
                header("Location: " . SERVER_ERROR);
                exit();
            }
        
            // Поиск элемента с нужным GUID
            $found = false;
            $active = false;
        
            // Рекурсивная функция для поиска GUID в меню
            function findGuid($items, $strGuid, &$found, &$active) {
                foreach ($items as $item) {
                    if (isset($item['guid']) && $item['guid'] === $strGuid) {
                        $found = true;
                        $active = $item['active'] ?? false;
                        return;
                    }
                    if (isset($item['dropdown']) && !empty($item['dropdown'])) {
                        findGuid($item['dropdown'], $strGuid, $found, $active);
                    }
                }
            }
        
            // Ищем GUID в меню
            findGuid($menuData['menu'], $strGuid, $found, $active);
        
            // Если GUID не найден
            if (!$found) {
                logger("ERROR", "GUID не найден: " . $strGuid . " [URL: $currentUrl]");
                header("Location: " . FORBIDDEN);
                exit();
            }
        
            // Проверка значения active
            if ($active === false) {
                logger("INFO", "Доступ запрещен для GUID: " . $strGuid . " [URL: $currentUrl]");
                header("Location: " . FORBIDDEN);
                exit();
            }
        
            // Если всё в порядке, продолжаем выполнение
            logger("INFO", "Доступ разрешен для GUID: " . $strGuid . " [URL: $currentUrl]");
        }



        //Инфо о успешном подключении 
        logger("INFO", "function.php успешно подключен");
?>