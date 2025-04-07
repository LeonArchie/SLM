<?php
session_start();
header('Content-Type: application/json');

// Получаем данные из POST-запроса
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data']);
    exit;
}

// Сохраняем данные в сессию
$_SESSION['access_token'] = $data['access_token'];
$_SESSION['refresh_token'] = $data['refresh_token'];
$_SESSION['userid'] = $data['user_id'];
$_SESSION['username'] = $data['user_name'];

echo json_encode(['success' => true]);
?>