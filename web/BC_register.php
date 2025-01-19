<?php
session_start();

// Проверка CSRF-токена
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Ошибка безопасности: неверный CSRF-токен.'
    ]);
    exit();
}

// Подключение к базе данных
require_once 'db_connect.php';

// Получение данных из формы
$email = trim($_POST['email']);
$username = trim($_POST['username']);
$password = trim($_POST['password']);
$roleid = trim($_POST['role']); // Оставляем как есть, без приведения к (int)

// Массив для хранения ошибок
$errors = [];

// Проверка каждого поля
if (empty($email)) {
    $errors['email'] = 'Поле "E-mail" обязательно для заполнения.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Некорректный формат E-mail.';
}

if (empty($username)) {
    $errors['username'] = 'Поле "Логин" обязательно для заполнения.';
} elseif (strlen($username) < 3) {
    $errors['username'] = 'Логин должен содержать не менее 3 символов.';
}

if (empty($password)) {
    $errors['password'] = 'Поле "Пароль" обязательно для заполнения.';
} elseif (strlen($password) < 6) {
    $errors['password'] = 'Пароль должен содержать не менее 6 символов.';
}

if (empty($roleid)) {
    $errors['role'] = 'Поле "Роль" обязательно для заполнения.';
}

// Если есть ошибки, возвращаем их
if (!empty($errors)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Ошибка валидации данных.',
        'errors' => $errors
    ]);
    exit();
}

// Чтение конфигурации из config.json
$configPath = __DIR__ . '/config/config.json'; // Убедитесь, что путь правильный

if (!file_exists($configPath)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Конфигурационный файл не найден.'
    ]);
    exit();
}

$configContent = file_get_contents($configPath);
if ($configContent === false) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Ошибка при чтении конфигурационного файла.'
    ]);
    exit();
}

$config = json_decode($configContent, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Ошибка при декодировании конфигурационного файла.'
    ]);
    exit();
}

// Проверка наличия ключа generator_url внутри объекта web
if (!isset($config['web']['generator_url'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Ключ "generator_url" отсутствует в конфигурационном файле.'
    ]);
    exit();
}

$generatorUrl = $config['web']['generator_url']; // Получаем URL из конфигурации

// Получение GUID из generator.php
$guidResponse = file_get_contents($generatorUrl);

if ($guidResponse === false) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Ошибка при получении GUID. Пожалуйста, попробуйте позже.'
    ]);
    exit();
}

// Декодируем JSON-ответ
$guidData = json_decode($guidResponse, true);

if (!$guidData || $guidData['status'] !== 'success' || empty($guidData['guid'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Ошибка при генерации уникального идентификатора.'
    ]);
    exit();
}

$userid = $guidData['guid']; // Используем GUID как userid

// Хэширование пароля
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Текущее время регистрации
$regtimes = date('Y-m-d H:i:s'); // Формат: ГГГГ-ММ-ДД ЧЧ:ММ:СС

try {
    // Подготовка SQL-запроса для вставки данных
    $sql = "INSERT INTO users (userid, userlogin, password_hash, email, roleid, regtimes) 
            VALUES (:userid, :userlogin, :password_hash, :email, :roleid, :regtimes)";
    $stmt = $pdo->prepare($sql);

    // Выполнение запроса с привязкой параметров
    $stmt->execute([
        ':userid' => $userid,
        ':userlogin' => $username,
        ':password_hash' => $password_hash,
        ':email' => $email,
        ':roleid' => $roleid, // Используем roleid как есть
        ':regtimes' => $regtimes // Передаем текущее время
    ]);

    // Успешный ответ
    echo json_encode([
        'status' => 'success',
        'message' => 'Регистрация прошла успешно!'
    ]);
    exit();
} catch (PDOException $e) {
    // Логирование ошибки
    error_log("Ошибка при регистрации пользователя: " . $e->getMessage());

    // Ошибка
    echo json_encode([
        'status' => 'error',
        'message' => 'Произошла ошибка при регистрации. Пожалуйста, попробуйте позже.'
    ]);
    exit();
}