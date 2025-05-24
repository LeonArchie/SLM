<?php
    session_start();
    header('Content-Type: application/json');

    // Проверяем, что запрос пришел с ожидаемых скриптов
    $allowed_referers = ['login.js', 'check_jwt.js'];
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $is_allowed_referer = false;

    foreach ($allowed_referers as $allowed) {
        if (strpos($referer, $allowed) !== false) {
            $is_allowed_referer = true;
            break;
        }
    }

    // Получаем данные из POST-запроса
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid data']);
        exit;
    }

    // Проверяем обязательные поля
    $required_fields = ['access_token', 'refresh_token', 'user_id', 'user_name'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            exit;
        }
    }

    // Сохраняем данные в сессию
    $_SESSION['access_token'] = $data['access_token'];
    $_SESSION['refresh_token'] = $data['refresh_token'];
    $_SESSION['userid'] = $data['user_id'];
    $_SESSION['username'] = $data['user_name'];

    echo json_encode(['success' => true]);
?>