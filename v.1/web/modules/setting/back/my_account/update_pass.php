<?php
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
    }

    $file_path = ROOT_PATH . '/include/function.php';

    if (!file_exists($file_path)) {
        echo json_encode(['success' => false, 'message' => 'Ошибка сервера: файл function.php не найден.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    require_once $file_path;

    startSessionIfNotStarted();

    $rawData = file_get_contents('php://input');
    if (!$rawData) {
        logger("ERROR", "Пустой запрос.");
        echo json_encode(['success' => false, 'message' => 'Пустой запрос.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $data = json_decode($rawData, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        logger("ERROR", "Неверный формат JSON.");
        echo json_encode(['success' => false, 'message' => 'Неверный формат JSON.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    if (empty($data['current_password']) || empty($data['new_password'])) {
        logger("ERROR", "Отсутствуют обязательные данные.");
        echo json_encode(['success' => false, 'message' => 'Необходимо заполнить все поля.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    if (empty($data['csrf_token'])) {
        logger("ERROR", "CSRF-токен не передан в данных.");
        echo json_encode(['success' => false, 'message' => 'Ошибка безопасности: CSRF-токен не передан.'], JSON_UNESCAPED_UNICODE);
        exit();
    }
    if ($data['csrf_token'] !== $_SESSION['csrf_token']) {
        logger("ERROR", "CSRF-токен не совпадает с ожидаемым значением.");
        echo json_encode(['success' => false, 'message' => 'Ошибка безопасности: неверный CSRF-токен.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $current_password = trim($data['current_password']);
    $new_password = trim($data['new_password']);

    $pdo = connectToDatabase();

    $userId = $_SESSION['userid'];
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE userid = :userid");
    $stmt->execute(['userid' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        logger("ERROR", "Пользователь не найден.");
        echo json_encode(['success' => false, 'message' => 'Пользователь не найден.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    if (!password_verify($current_password, $user['password_hash'])) {
        logger("ERROR", "Неверный текущий пароль.");
        echo json_encode(['success' => false, 'message' => 'Неверный текущий пароль.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE users SET password_hash = :password_hash WHERE userid = :userid");
    $result = $stmt->execute(['password_hash' => $new_password_hash, 'userid' => $userId]);

    if ($result) {
        logger("INFO", "Пароль успешно обновлен.");
        echo json_encode(['success' => true, 'message' => 'Пароль успешно изменен.'], JSON_UNESCAPED_UNICODE);
    } else {
        logger("ERROR", "Ошибка при обновлении пароля.");
        echo json_encode(['success' => false, 'message' => 'Ошибка при изменении пароля.'], JSON_UNESCAPED_UNICODE);
    }
?>