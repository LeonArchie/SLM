<?php
// Настройка логгирования
ini_set('error_log', '/var/app.log'); // Указываем путь к файлу логов
ini_set('log_errors', 1); // Включаем запись ошибок в лог
ini_set('display_errors', 0); // Отключаем вывод ошибок на экран (для production)

// Основной блок кода
try {
    // Путь к конфигурационному файлу
    // Указываем правильный относительный путь к файлу config.json
    $configFile = __DIR__ . '/config/config.json'; // Относительный путь от текущего файла

    // Проверка, существует ли файл конфигурации
    if (!file_exists($configFile)) {
        // Если файл не найден, выбрасываем исключение с сообщением об ошибке
        throw new Exception("Конфигурационный файл не найден: " . $configFile);
    }

    // Чтение содержимого файла конфигурации
    $configContent = file_get_contents($configFile);

    // Проверка, удалось ли прочитать файл
    if ($configContent === false) {
        throw new Exception("Не удалось прочитать файл конфигурации: " . $configFile);
    }

    // Декодирование JSON-содержимого в ассоциативный массив
    $config = json_decode($configContent, true);

    // Проверка на ошибки при декодировании JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Если произошла ошибка, выбрасываем исключение с сообщением об ошибке
        throw new Exception("Ошибка при декодировании JSON: " . json_last_error_msg());
    }

    // Проверка наличия раздела 'db' в конфигурации
    if (!isset($config['db'])) {
        throw new Exception("Раздел 'db' отсутствует в конфигурационном файле.");
    }

    // Извлечение параметров подключения к базе данных из конфигурации
    $dbConfig = $config['db']; // Получаем раздел 'db' из конфигурации

    // Проверка наличия всех необходимых параметров подключения
    $requiredKeys = ['host', 'port', 'name', 'user', 'password'];
    foreach ($requiredKeys as $key) {
        if (!isset($dbConfig[$key])) {
            throw new Exception("Не хватает параметра '$key' в конфигурации базы данных.");
        }
    }

    // Извлечение параметров подключения
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

    // Возвращаем объект PDO для использования в других частях приложения
    return $pdo;
} catch (Exception $e) {
    // Логирование ошибки подключения к базе данных
    error_log("Ошибка подключения к базе данных: " . $e->getMessage());

    // Перенаправление на страницу 50x.html
    header("Location: /50x.html");
    exit(); // Завершаем выполнение скрипта
}
?>