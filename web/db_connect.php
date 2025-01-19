<?php
// Функция для чтения конфигурационного файла
function readConfig($configFile) {
    // Проверка, существует ли файл конфигурации
    if (!file_exists($configFile)) {
        // Если файл не найден, выбрасываем исключение с сообщением об ошибке
        throw new Exception("Конфигурационный файл не найден: " . $configFile);
    }

    // Чтение содержимого файла конфигурации
    $configContent = file_get_contents($configFile);

    // Декодирование JSON-содержимого в ассоциативный массив
    $config = json_decode($configContent, true);

    // Проверка на ошибки при декодировании JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Если произошла ошибка, выбрасываем исключение с сообщением об ошибке
        throw new Exception("Ошибка при декодировании JSON: " . json_last_error_msg());
    }

    // Возвращаем массив с конфигурацией
    return $config;
}

// Основной блок кода
try {
    // Путь к конфигурационному файлу
    // __DIR__ — магическая константа, возвращающая директорию текущего файла
    // '/../config/config.json' — относительный путь к файлу конфигурации
    $configFile = __DIR__ . 'config/config.json';

    // Чтение конфигурационного файла с помощью функции readConfig
    $config = readConfig($configFile);

    // Извлечение параметров подключения к базе данных из конфигурации
    $dbConfig = $config['db']; // Получаем раздел 'db' из конфигурации
    $host = $dbConfig['host']; // Хост базы данных
    $port = $dbConfig['port']; // Порт базы данных
    $dbname = $dbConfig['name']; // Имя базы данных
    $user = $dbConfig['user']; // Имя пользователя базы данных
    $password = $dbConfig['password']; // Пароль пользователя базы данных

    // Формирование строки подключения к PostgreSQL
    // Используем параметры, полученные из конфигурации
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;options='--client_encoding=UTF8'";

    // Создание подключения к базе данных с использованием PDO
    // Передаем строку подключения, имя пользователя и пароль
    $pdo = new PDO($dsn, $user, $password);

    // Установка атрибутов PDO
    // PDO::ATTR_ERRMODE — режим обработки ошибок
    // PDO::ERRMODE_EXCEPTION — выбрасывать исключения при ошибках
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // PDO::ATTR_DEFAULT_FETCH_MODE — режим выборки данных по умолчанию
    // PDO::FETCH_ASSOC — возвращать данные в виде ассоциативного массива
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Логирование ошибки подключения к базе данных
    // error_log — запись сообщения об ошибке в лог-файл
    error_log("Ошибка подключения к базе данных: " . $e->getMessage());

    // Завершение выполнения скрипта с выводом сообщения об ошибке
    die("Произошла ошибка. Пожалуйста, попробуйте позже.");
}
?>