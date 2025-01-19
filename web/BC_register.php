<?php
session_start();

// Включение логирования ошибок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
$roleid = trim($_POST['role']);
$usernames = trim($_POST['usernames']); // Новое поле: Имя пользователя

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

if (empty($usernames)) {
    $errors['usernames'] = 'Поле "Имя пользователя" обязательно для заполнения.';
} elseif (strlen($usernames) < 2) {
    $errors['usernames'] = 'Имя пользователя должно содержать не менее 2 символов.';
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

// Локальная генерация GUID (если generator.php недоступен)
function generateGUID() {
    if (function_exists('com_create_guid') === true) {
        return trim(com_create_guid(), '{}');
    }
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

$userid = generateGUID(); // Используем локальную генерацию GUID

// Хэширование пароля
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Текущее время регистрации
$regtimes = date('Y-m-d H:i:s');

try {
    // Подготовка SQL-запроса для вставки данных
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
        ':usernames' => $usernames // Новое поле: Имя пользователя
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