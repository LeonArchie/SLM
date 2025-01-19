<?php
// Начало сессии для работы с сессионными переменными
session_start();
// Настройка логгирования
ini_set('error_log', '/var/app.log'); // Указываем путь к файлу логов
ini_set('log_errors', 1); // Включаем запись ошибок в лог
ini_set('display_errors', 0); // Отключаем вывод ошибок на экран (для production)
// Проверка CSRF-токена для защиты от межсайтовой подделки запросов
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    // Установка заголовка для возврата JSON
    header('Content-Type: application/json');
    // Возврат ошибки, если CSRF-токен не совпадает
    echo json_encode([
        'status' => 'error',
        'message' => 'Ошибка безопасности: неверный CSRF-токен.'
    ]);
    // Завершение выполнения скрипта
    exit();
}

// Подключение к базе данных
require_once 'db_connect.php';

// Функция для чтения конфигурационного файла
function readConfig($configFile) {
    // Проверка, существует ли файл конфигурации
    if (!file_exists($configFile)) {
        // Выброс исключения, если файл не найден
        throw new Exception("Конфигурационный файл не найден: " . $configFile);
    }

    // Чтение содержимого файла конфигурации
    $configContent = file_get_contents($configFile);
    // Декодирование JSON в ассоциативный массив
    $config = json_decode($configContent, true);

    // Проверка на ошибки при декодировании JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Выброс исключения, если JSON некорректен
        throw new Exception("Ошибка при декодировании JSON: " . json_last_error_msg());
    }

    // Возврат массива с конфигурацией
    return $config;
}

// Получение данных из формы и удаление лишних пробелов
$email = trim($_POST['email']);
$username = trim($_POST['username']);
$password = trim($_POST['password']);
$roleid = trim($_POST['role']);
$usernames = trim($_POST['usernames']);

// Массив для хранения ошибок валидации
$errors = [];

// Валидация поля "E-mail"
if (empty($email)) {
    $errors['email'] = 'Поле "E-mail" обязательно для заполнения.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Некорректный формат E-mail.';
}

// Валидация поля "Логин"
if (empty($username)) {
    $errors['username'] = 'Поле "Логин" обязательно для заполнения.';
} elseif (strlen($username) < 3) {
    $errors['username'] = 'Логин должен содержать не менее 3 символов.';
}

// Валидация поля "Пароль"
if (empty($password)) {
    $errors['password'] = 'Поле "Пароль" обязательно для заполнения.';
} elseif (strlen($password) < 6) {
    $errors['password'] = 'Пароль должен содержать не менее 6 символов.';
}

// Валидация поля "Роль"
if (empty($roleid)) {
    $errors['role'] = 'Поле "Роль" обязательно для заполнения.';
}

// Валидация поля "Имя пользователя"
if (empty($usernames)) {
    $errors['usernames'] = 'Поле "Имя пользователя" обязательно для заполнения.';
} elseif (strlen($usernames) < 2) {
    $errors['usernames'] = 'Имя пользователя должно содержать не менее 2 символов.';
}

// Если есть ошибки валидации, возвращаем их
if (!empty($errors)) {
    // Установка заголовка для возврата JSON
    header('Content-Type: application/json');
    // Возврат ошибок валидации
    echo json_encode([
        'status' => 'error',
        'message' => 'Ошибка валидации данных.',
        'errors' => $errors
    ]);
    // Завершение выполнения скрипта
    exit();
}

// Проверка существования пользователя с таким же логином или email
$sqlCheck = "SELECT userid FROM users WHERE userlogin = :userlogin OR email = :email";
$stmtCheck = $pdo->prepare($sqlCheck);
$stmtCheck->execute([':userlogin' => $username, ':email' => $email]);

// Если пользователь с таким логином или email уже существует
if ($stmtCheck->rowCount() > 0) {
    // Установка заголовка для возврата JSON
    header('Content-Type: application/json');
    // Возврат ошибки
    echo json_encode([
        'status' => 'error',
        'message' => 'Пользователь с таким логином или email уже существует.'
    ]);
    // Завершение выполнения скрипта
    exit();
}

// Функция для получения GUID от внешнего генератора
function getExternalGUID($generatorUrl) {
    // Отправка запроса к внешнему генератору GUID
    $response = file_get_contents($generatorUrl);
    // Проверка на ошибки при получении ответа
    if ($response === false) {
        throw new Exception("Не удалось получить GUID от внешнего генератора.");
    }

    // Декодирование JSON-ответа
    $data = json_decode($response, true);
    // Проверка на ошибки при декодировании JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Ошибка при декодировании JSON от внешнего генератора.");
    }

    // Проверка наличия GUID в ответе
    if (!isset($data['guid'])) {
        throw new Exception("Внешний генератор не вернул GUID.");
    }

    // Возврат GUID
    return $data['guid'];
}

// Функция для локальной генерации GUID (если внешний генератор недоступен)
function generateLocalGUID() {
    // Генерация GUID в формате xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
    );
}

try {
    // Чтение конфигурационного файла
    $config = readConfig(__DIR__ . '/../config/config.json');
    // Получение URL генератора GUID из конфигурации
    $generatorUrl = $config['web']['generator_url'];

    // Попытка получить GUID от внешнего генератора
    try {
        $userid = getExternalGUID($generatorUrl);
    } catch (Exception $e) {
        // Если внешний генератор недоступен, используем локальную генерацию
        error_log("Ошибка при получении GUID от внешнего генератора: " . $e->getMessage());
        $userid = generateLocalGUID();
    }

    // Хэширование пароля для безопасного хранения в базе данных
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    // Получение текущего времени для записи времени регистрации
    $regtimes = date('Y-m-d H:i:s');

    // Подготовка SQL-запроса для вставки данных в таблицу users
    $sql = "INSERT INTO users (userid, userlogin, password_hash, email, roleid, regtimes, usernames) 
            VALUES (:userid, :userlogin, :password_hash, :email, :roleid, :regtimes, :usernames)";
    $stmt = $pdo->prepare($sql);
    // Выполнение запроса с привязкой параметров
    $stmt->execute([
        ':userid' => $userid,
        ':userlogin' => $username,
        ':password_hash' => $password_hash,
        ':email' => $email,
        ':roleid' => $roleid,
        ':regtimes' => $regtimes,
        ':usernames' => $usernames
    ]);

    // Установка заголовка для возврата JSON
    header('Content-Type: application/json');
    // Возврат успешного ответа
    echo json_encode([
        'status' => 'success',
        'message' => 'Регистрация прошла успешно!'
    ]);
    // Завершение выполнения скрипта
    exit();
} catch (PDOException $e) {
    // Логирование ошибки при выполнении SQL-запроса
    error_log("Ошибка при регистрации пользователя: " . $e->getMessage());
    error_log("SQL: " . $sql);
    error_log("Параметры: " . print_r([
        ':userid' => $userid,
        ':userlogin' => $username,
        ':password_hash' => $password_hash,
        ':email' => $email,
        ':roleid' => $roleid,
        ':regtimes' => $regtimes,
        ':usernames' => $usernames
    ], true));

    // Установка заголовка для возврата JSON
    header('Content-Type: application/json');
    // Возврат ошибки
    echo json_encode([
        'status' => 'error',
        'message' => 'Произошла ошибка при регистрации. Пожалуйста, попробуйте позже.'
    ]);
    // Завершение выполнения скрипта
    exit();
}
?>