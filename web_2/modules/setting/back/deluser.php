<?php
session_start();
require_once __DIR__ . '/../include/platform.php';

logger("INFO", "Начало выполнения скрипта deluser.php.");

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['user_ids']) || !is_array($data['user_ids'])) {
    logger("ERROR", "Отсутствуют или некорректные ID пользователей.");
    echo json_encode(['success' => false, 'message' => 'Некорректные данные.'], JSON_UNESCAPED_UNICODE);
    exit();
}

$userIds = array_map('intval', $data['user_ids']); // Преобразуем ID в целые числа

$pdo = connectToDatabase();
logger("DEBUG", "Успешное подключение к базе данных.");

try {
    $stmt = $pdo->prepare("DELETE FROM users WHERE userid IN (" . implode(',', $userIds) . ")");
    $result = $stmt->execute();

    if ($result) {
        logger("INFO", "Пользователи успешно удалены.");
        echo json_encode(['success' => true, 'message' => 'Пользователи успешно удалены.'], JSON_UNESCAPED_UNICODE);
    } else {
        logger("ERROR", "Ошибка при удалении пользователей.");
        echo json_encode(['success' => false, 'message' => 'Ошибка при удалении пользователей.'], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    logger("ERROR", "Исключение при удалении пользователей: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Произошла ошибка при удалении пользователей.'], JSON_UNESCAPED_UNICODE);
}
?>