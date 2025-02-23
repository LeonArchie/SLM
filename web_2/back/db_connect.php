<?php
require_once '/../include/function.php';

// Логирование начала выполнения скрипта
logger("INFO", "Начало выполнения скрипта db_connect.php.");

try {
    // Путь к конфигурационному файлу
    $configFile = __DIR__ . '/../config/config.json';
    logger("DEBUG", "Попытка чтения конфигурационного файла: " . $configFile);

    // Проверка, существует ли файл конфигурации
    if (!file_exists($configFile)) {
        logger("ERROR", "Конфигурационный файл не найден: " . $configFile);
        throw new Exception("Конфигурационный файл не найден: " . $configFile);
    }

    // Чтение содержимого файла конфигурации
    $configContent = file_get_contents($configFile);
    if ($configContent === false) {
        logger("ERROR", "Не удалось прочитать файл конфигурации: " . $configFile);
        throw new Exception("Не удалось прочитать файл конфигурации: " . $configFile);
    }

    // Декодирование JSON-содержимого в ассоциативный массив
    $config = json_decode($configContent, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        logger("ERROR", "Ошибка при декодировании JSON: " . json_last_error_msg());
        throw new Exception("Ошибка при декодировании JSON: " . json_last_error_msg());
    }

    logger("INFO", "Конфигурационный файл успешно прочитан.");

    // Проверка наличия раздела 'db' в конфигурации
    if (!isset($config['db'])) {
        logger("ERROR", "Раздел 'db' отсутствует в конфигурационном файле.");
        throw new Exception("Раздел 'db' отсутствует в конфигурационном файле.");
    }

    // Извлечение параметров подключения к базе данных из конфигурации
    $dbConfig = $config['db'];
    logger("INFO", "Раздел 'db' успешно извлечен из конфигурации.");

    // Проверка наличия всех необходимых параметров подключения
    $requiredKeys = ['host', 'port', 'name', 'user', 'password'];
    foreach ($requiredKeys as $key) {
        if (!isset($dbConfig[$key])) {
            logger("ERROR", "Не хватает параметра '$key' в конфигурации базы данных.");
            throw new Exception("Не хватает параметра '$key' в конфигурации базы данных.");
        }
    }

    logger("INFO", "Все необходимые параметры подключения присутствуют в конфигурации.");

    // Извлечение параметров подключения
    $host = $dbConfig['host'];
    $port = $dbConfig['port'];
    $dbname = $dbConfig['name'];
    $user = $dbConfig['user'];
    $password = $dbConfig['password'];

    logger("DEBUG", "Параметры подключения:");
    logger("DEBUG", "Хост: " . $host);
    logger("DEBUG", "Порт: " . $port);
    logger("DEBUG", "Имя базы данных: " . $dbname);
    logger("DEBUG", "Пользователь: " . $user);
    logger("DEBUG", "Пароль: " . (empty($password) ? "не указан" : "указан"));

    // Формирование строки подключения к PostgreSQL
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;options='--client_encoding=UTF8'";
    logger("DEBUG", "Строка подключения к базе данных: " . $dsn);

    // Создание подключения к базе данных с использованием PDO
    logger("INFO", "Попытка подключения к базе данных...");
    $pdo = new PDO($dsn, $user, $password);

    // Установка атрибутов PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    logger("INFO", "Подключение к базе данных успешно установлено.");
    logger("INFO", "Атрибуты PDO настроены: ERRMODE_EXCEPTION, FETCH_ASSOC.");

    // Возвращаем объект PDO для использования в других частях приложения
    return $pdo;
} catch (Exception $e) {
    // Логирование ошибки подключения к базе данных
    logger("ERROR", "Ошибка подключения к базе данных: " . $e->getMessage());

    // Перенаправление на страницу 50x.html
    logger("ERROR", "Перенаправление на страницу 50x.html.");
    header("Location: " . SERVER_ERROR);
    exit(); // Завершаем выполнение скрипта
}
?>