<?php
session_start();
require_once __DIR__ . '/../include/platform.php';

logger("INFO", "Начало выполнения скрипта create-user.php.");

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['full_name']) || empty($data['userlogin']) || empty($data['password']) || empty($data['email'])) {
    logger("ERROR", "Отсутствуют обязательные данные.");
    echo json_encode(['success' => false, 'message' => 'Необходимо заполнить все поля.'], JSON_UNESCAPED_UNICODE);
    exit();
}

$full_name = trim($data['full_name']);
$userlogin = trim($data['userlogin']);
$password_hash = password_hash(trim($data['password']), PASSWORD_DEFAULT);
$email = trim($data['email']);

$pdo = connectToDatabase();
logger("DEBUG", "Успешное подключение к базе данных.");

$stmt = $pdo->prepare("INSERT INTO users (full_name, userlogin, password_hash, email) VALUES (:full_name, :userlogin, :password_hash, :email)");
$result = $stmt->execute([
    'full_name' => $full_name,
    'userlogin' => $userlogin,
    'password_hash' => $password_hash,
    'email' => $email
]);

if ($result) {
    logger("INFO", "Пользователь успешно создан.");
    echo json_encode(['success' => true, 'message' => 'Пользователь успешно создан.'], JSON_UNESCAPED_UNICODE);
} else {
    logger("ERROR", "Ошибка при создании пользователя.");
    echo json_encode(['success' => false, 'message' => 'Ошибка при создании пользователя.'], JSON_UNESCAPED_UNICODE);
}
?>