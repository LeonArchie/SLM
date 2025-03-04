<?php
    // Определяем корень веб-сервера
        define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);

    // Блок переменных
        define('LOGOUT_PATH', '/back/logout.php');
        define('LOGGER_PATH', '/var/log/slm/web/web.log');
        define('CONFIG_PATH', ROOT_PATH . '/config/config.json');
        define('CONFIG_MENU',  ROOT_PATH . '/config/modules.json');
        define('FORBIDDEN', 'err/403.html');
        define('NOT_FOUND', 'err/404.html');
        define('SERVER_ERROR', 'err/50x.html');

    //Отбрасываем построенные переменные в лог
        //logger("INFO", "Начато подключение function.php");
        //logger("DEBUG", "Константа LOGOUT_PATH = " . LOGOUT_PATH);
        //logger("DEBUG", "Константа LOGGER_PATH = " . LOGGER_PATH);
        //logger("DEBUG", "Константа CONFIG_PATH = " . CONFIG_PATH);
        //logger("DEBUG", "Константа CONFIG_MENU = " . CONFIG_MENU);
        //logger("DEBUG", "Константа FORBIDDEN = " . FORBIDDEN);
        //logger("DEBUG", "Константа NOT_FOUND = " . NOT_FOUND);
        //logger("DEBUG", "Константа SERVER_ERROR = " . SERVER_ERROR);


    // Функция для запуска сессии, если она еще не запущена
        function startSessionIfNotStarted() {
            //logger("INFO", "Попытка запуска сессии.");
            
            if (session_status() === PHP_SESSION_DISABLED) {
                logger("ERROR", "Сессии отключены. Невозможно запустить сессию.");
                throw new Exception("Сессии отключены.");
            }
        
            if (session_status() === PHP_SESSION_NONE) {
                //logger("INFO", "Сессия не активна. Запуск сессии...");
                session_start();
                logger("INFO", "Сессия успешно запущена. ID сессии: " . session_id());
            } else {
                logger("INFO", "Сессия уже активна. ID сессии: " . session_id());
            }
        }

    // Функция для проверки авторизации пользователя
        function checkAuth() {
            //logger("INFO", "Начало проверки авторизации пользователя.");

            // Проверка наличия имени пользователя в сессии
            if (!isset($_SESSION['username'])) {
                logger("WARNING", "Пользователь не авторизован: отсутствует username в сессии.");
                header("Location: " . LOGOUT_PATH);
                exit();
            }

            // Проверка наличия cookie с session_id
            if (!isset($_COOKIE['session_id'])) {
                logger("WARNING", "Пользователь не авторизован: отсутствует session_id в cookie.");
                header("Location: " . LOGOUT_PATH);
                exit();
            }

            // Проверка совпадения session_id из cookie и текущей сессии
            if ($_COOKIE['session_id'] !== session_id()) {
                logger("ERROR", "Пользователь не авторизован: session_id из cookie не совпадает с текущей сессией.");
                //logger("DEBUG", "session_id из cookie: " . $_COOKIE['session_id']);
                //logger("DEBUG", "session_id из сессии: " . session_id());
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
            //logger("INFO", "Начало генерации GUID.");
        
            if (function_exists('com_create_guid') === true) {
                // Логирование использования встроенной функции com_create_guid
                logger("INFO", "Используется встроенная функция com_create_guid для генерации GUID.");
                $guid = trim(com_create_guid(), '{}');
                logger("INFO", "Сгенерирован GUID с использованием com_create_guid: " . $guid);
                return $guid;
            }
        
            // Логирование начала ручной генерации GUID
            logger("WARNING", "Встроенная функция com_create_guid недоступна. Ручная генерации GUID.");
        
            // Генерация 16 байт случайных данных
            $data = random_bytes(16);
            //logger("INFO", "Сгенерировано 16 байт случайных данных для GUID.");
        
            // Установка версии (4) и битов (10)
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Версия 4
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Биты 10
            //logger("INFO", "Установлена версия и биты для GUID.");
        
            // Форматирование GUID в стандартный вид
            $guid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
            logger("WARNING", "Сгенерирован GUID вручную: " . $guid);
        
            return $guid;
        }




        function FROD($moduleId = null, $pageId = null) {
            // Логирование начала выполнения функции
            //logger("INFO", "Начало выполнения функции FROD.");
        
            // Получаем текущий URL
            $currentUrl = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'UNKNOWN';
        
            // Проверяем, определена ли константа CONFIG_PATH
            if (!defined('CONFIG_PATH')) {
                logger("ERROR", "Переменная CONFIG_PATH не определена. [URL: $currentUrl]");
                header("Location: " . SERVER_ERROR);
                exit();
            }
           //logger("DEBUG", "Переменная CONFIG_PATH определена. [URL: $currentUrl]");
        
            // Проверяем существование файла config.json
            if (!file_exists(CONFIG_PATH)) {
                logger("ERROR", "Файл config.json не найден: " . CONFIG_PATH);
                header("Location: " . SERVER_ERROR);
                exit();
            }
            //logger("DEBUG", "Файл config.json найден: " . CONFIG_PATH);
        
            // Чтение файла config.json
            $configJson = file_get_contents(CONFIG_PATH);
            if ($configJson === false) {
                logger("ERROR", "Ошибка при чтении файла config.json: " . CONFIG_PATH);
                header("Location: " . SERVER_ERROR);
                exit();
            }
            //logger("DEBUG", "Чтение файла config.json успешно: " . CONFIG_PATH);
        
            // Декодирование JSON
            $configData = json_decode($configJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                logger("ERROR", "Ошибка при декодировании config.json: " . json_last_error_msg());
                header("Location: " . SERVER_ERROR);
                exit();
            }
            //logger("DEBUG", "Декодирование config.json успешно ");
        
            // Проверяем статус FROD
            $frodEnabled = isset($configData['web']['frod']) && 
                           (strtolower((string)$configData['web']['frod']) === 'true' || 
                            $configData['web']['frod'] === true);
        
            if ($frodEnabled) {
                //logger("INFO", "FROD включен.");
            } else {
                logger("WARNING", "FROD выключен.");
                return; // Если FROD выключен, пропускаем все проверки
            }
        
            // Проверяем, есть ли текущий URL в списке frod_ignore
            $frodIgnore = isset($configData['web']['frod_ignore']) ? $configData['web']['frod_ignore'] : [];
            if (in_array($currentUrl, $frodIgnore, true)) {
                logger("INFO", "Проверка FROD пропущена для URL: $currentUrl (находится в списке frod_ignore).");
                return;
            }
        
            // Подключаемся к базе данных через функцию connectToDatabase()
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
        
            // Проверка прав администратора
            try {
                // Выполнение запроса для получения roleid для роли 'Администратор'
                $stmt = $pdo->prepare("SELECT roleid FROM name_rol WHERE names_rol = :role_name");
                $stmt->execute([':role_name' => 'Администратор']);
                $adminRole = $stmt->fetchColumn();
        
                // Проверка наличия значения roleid в сессии
                if (isset($_SESSION['roleid']) && $_SESSION['roleid'] == $adminRole) {
                    logger("INFO", "Доступ разрешен для администратора. [URL: $currentUrl]");
                    return; // Завершаем функцию для администратора
                } else {
                    //logger("INFO", "Пользователь не является администратором. Продолжение проверки FROD. [URL: $currentUrl]");
                }
            } catch (Exception $e) {
                logger("ERROR", "Ошибка при проверке прав администратора: " . $e->getMessage());
                header("Location: " . SERVER_ERROR);
                exit();
            }
        
            // Сценарий FROD для одного параметра (moduleId)
            if ($moduleId !== null && $pageId === null) {
                logger("DEBUG", "Запущен сценарий FROD для модуля: $moduleId");
        
                // Проверяем Module ID
                if (empty($moduleId)) {
                    logger("ERROR", "Module ID не передан. [URL: $currentUrl]");
                    header("Location: " . FORBIDDEN);
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
        
                // Поиск элемента с нужным guid
                $found = false;
                $active = false;
        
                // Рекурсивный поиск guid в меню
                foreach ($menuData['menu'] as $item) {
                    if (isset($item['guid']) && $item['guid'] === $moduleId) {
                        $found = true;
                        $active = $item['active'] ?? false;
                        break;
                    }
                    if (isset($item['dropdown']) && !empty($item['dropdown'])) {
                        foreach ($item['dropdown'] as $dropdownItem) {
                            if (isset($dropdownItem['guid']) && $dropdownItem['guid'] === $moduleId) {
                                $found = true;
                                $active = $dropdownItem['active'] ?? false;
                                break 2; // Выходим из обоих циклов
                            }
                        }
                    }
                }
        
                // Если guid не найден
                if (!$found) {
                    logger("ERROR", "GUID не найден: $moduleId [URL: $currentUrl]");
                    header("Location: " . FORBIDDEN);
                    exit();
                }
        
                // Проверка значения active
                if ($active === false) {
                    logger("WARNING", "Доступ запрещен для GUID: $moduleId [URL: $currentUrl]");
                    header("Location: " . FORBIDDEN);
                    exit();
                }
        
                // Проверка прав доступа через privileges для module_id
                try {
                    $stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM privileges WHERE userid = :userid AND module_id = :module_id");
                    $stmt->execute([':userid' => $_SESSION['userid'], ':module_id' => $moduleId]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
                    if ($result['count'] <= 0) {
                        logger("ERROR", "У пользователя нет прав на доступ к GUID: $moduleId [URL: $currentUrl]");
                        header("Location: " . FORBIDDEN);
                        exit();
                    }
                } catch (Exception $e) {
                    logger("ERROR", "Ошибка при проверке прав доступа: " . $e->getMessage());
                    header("Location: " . SERVER_ERROR);
                    exit();
                }
        
                logger("INFO", "Доступ разрешен для GUID: $moduleId [URL: $currentUrl]");
                return;
            }
        
            // Сценарий FROD для двух параметров (moduleId и pageId)
            if ($moduleId !== null && $pageId !== null) {
                logger("DEBUG", "Запущен сценарий FROD для модуля: $moduleId и страницы: $pageId");
        
                // Проверка прав доступа через privileges для page_id
                try {
                    $stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM privileges WHERE userid = :userid AND module_id = :module_id AND page_id = :page_id");
                    $stmt->execute([':userid' => $_SESSION['userid'], ':module_id' => $moduleId, ':page_id' => $pageId]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
                    if ($result['count'] <= 0) {
                        logger("ERROR", "У пользователя нет прав на доступ к GUID: $moduleId и Page ID: $pageId [URL: $currentUrl]");
                        header("Location: " . FORBIDDEN);
                        exit();
                    }
                } catch (Exception $e) {
                    logger("ERROR", "Ошибка при проверке прав доступа: " . $e->getMessage());
                    header("Location: " . SERVER_ERROR);
                    exit();
                }
        
                logger("INFO", "Доступ разрешен для GUID: $moduleId и Page ID: $pageId [URL: $currentUrl]");
                return;
            }
        
            // Если ни один из параметров не передан
            logger("ERROR", "Не переданы необходимые параметры для проверки FROD. [URL: $currentUrl]");
            header("Location: " . FORBIDDEN);
            exit();
        }






        
        function connectToDatabase() {
            static $pdo = null; // Статическая переменная для хранения соединения
        
            if ($pdo !== null) {
                //logger("INFO", "Используется существующее соединение.");
                return $pdo;
            }
        
            try {
                //logger("INFO", "Начало выполнения функции connectToDatabase.");
        
                // Путь к конфигурационному файлу
                $configFile = __DIR__ . '/../config/config.json';
                //logger("DEBUG", "Попытка чтения конфигурационного файла: " . $configFile);
        
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
                //logger("INFO", "Раздел 'db' успешно извлечен из конфигурации.");
        
                // Проверка наличия всех необходимых параметров подключения
                $requiredKeys = ['host', 'port', 'name', 'user', 'password'];
                foreach ($requiredKeys as $key) {
                    if (!isset($dbConfig[$key])) {
                        logger("ERROR", "Не хватает параметра '$key' в конфигурации базы данных.");
                        throw new Exception("Не хватает параметра '$key' в конфигурации базы данных.");
                    }
                }
                logger("INFO", "Все необходимые параметры подключения к базе данных присутствуют в конфигурации.");
        
                // Извлечение параметров подключения
                $host = $dbConfig['host'];
                $port = $dbConfig['port'];
                $dbname = $dbConfig['name'];
                $user = $dbConfig['user'];
                $password = $dbConfig['password'];
                //logger("DEBUG", "Параметры подключения:");
                //logger("DEBUG", "Хост: " . $host);
                //logger("DEBUG", "Порт: " . $port);
                //logger("DEBUG", "Имя базы данных: " . $dbname);
                //logger("DEBUG", "Пользователь: " . $user);
                //logger("DEBUG", "Пароль: " . (empty($password) ? "не указан" : "указан"));
        
                // Формирование строки подключения к PostgreSQL
                $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;options='--client_encoding=UTF8'";
                //logger("DEBUG", "Строка подключения к базе данных: " . $dsn);
        
                // Создание подключения к базе данных с использованием PDO
                //logger("INFO", "Попытка подключения к базе данных...");
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
              
                logger("INFO", "Подключение к базе данных успешно установлено.");
                logger("INFO", "Атрибуты PDO и настройки PostgreSQL настроены.");
        
                return $pdo;
            } catch (Exception $e) {
                logger("ERROR", "Ошибка подключения к базе данных: " . $e->getMessage());
                header("Location: " . SERVER_ERROR);
                exit();
            }
        }

        //Инфо о успешном подключении 
        logger("INFO", "Функции платформы подключены");
?>