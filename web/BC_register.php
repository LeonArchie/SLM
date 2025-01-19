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
$roleid = (int)$_POST['role']; // Приводим к целому числу

// Проверка, что все поля заполнены
if (empty($email) || empty($username) || empty($password) || empty($roleid)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Все поля обязательны для заполнения!'
    ]);
    exit();
}

// Чтение конфигурации из config.json
$configPath = __DIR__ . '/../config/config.json'; // Путь к файлу config.json
if (!file_exists($configPath)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Конфигурационный файл не найден.'
    ]);
    exit();
}

$config = json_decode(file_get_contents($configPath), true);
if (json_last_error() !== JSON_ERROR_NONE || !isset($config['generator_url'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Ошибка при чтении конфигурационного файла.'
    ]);
    exit();
}

$generatorUrl = $config['generator_url']; // Получаем URL из конфигурации

// Получение GUID из generator.php
$guidResponse = file_get_contents($generatorUrl);

if ($guidResponse === false) {
    // Ошибка при запросе к generator.php
    echo json_encode([
        'status' => 'error',
        'message' => 'Ошибка при получении GUID. Пожалуйста, попробуйте позже.'
    ]);
    exit();
}

// Декодируем JSON-ответ
$guidData = json_decode($guidResponse, true);

if (!$guidData || $guidData['status'] !== 'success' || empty($guidData['guid'])) {
    // Ошибка при декодировании или неверный формат ответа
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
        ':roleid' => $roleid,
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