<?php
// Функция для чтения конфигурационного файла
function readConfig($configFile) {
    if (!file_exists($configFile)) {
        throw new Exception("Конфигурационный файл не найден: " . $configFile);
    }

    $configContent = file_get_contents($configFile);
    $config = json_decode($configContent, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Ошибка при декодировании JSON: " . json_last_error_msg());
    }

    return $config;
}

try {
    // Путь к конфигурационному файлу
    $configFile = __DIR__ . '/../config/config.json'; // Обновленный путь

    // Чтение конфигурационного файла
    $config = readConfig($configFile);

    // Извлечение параметров подключения к базе данных
    $dbConfig = $config['db'];
    $host = $dbConfig['host'];
    $port = $dbConfig['port'];
    $dbname = $dbConfig['name'];
    $user = $dbConfig['user'];
    $password = $dbConfig['password'];

    // Строка подключения к PostgreSQL
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;options='--client_encoding=UTF8'";

    // Создание подключения к базе данных
    $pdo = new PDO($dsn, $user, $password);

    // Установка атрибутов PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Режим ошибок: выбрасывать исключения
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // Режим выборки данных: ассоциативный массив
} catch (Exception $e) {
    error_log("Ошибка подключения к базе данных: " . $e->getMessage());
    die("Произошла ошибка. Пожалуйста, попробуйте позже.");
}
?>