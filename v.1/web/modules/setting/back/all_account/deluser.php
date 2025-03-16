<?php
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
    }
    $file_path = ROOT_PATH . '/include/function.php';

    if (!file_exists($file_path)) {
        echo json_encode(['success' => false, 'message' => 'Ошибка сервера: файл function.php не найден.']);
        exit();
    }

    require_once $file_path;

    startSessionIfNotStarted();

    $data = json_decode(file_get_contents('php://input'), true);

    // Проверка токена
    if (empty($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
        logger("ERROR", "Неверный CSRF-токен.");
        echo json_encode(['success' => false, 'message' => 'Неверный CSRF-токен.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Проверка наличия массива user_ids
    if (empty($data['user_ids']) || !is_array($data['user_ids'])) {
        logger("ERROR", "Отсутствуют или некорректные ID пользователей.");
        echo json_encode(['success' => false, 'message' => 'Некорректные данные.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Проверка, что userId не содержится в массиве user_ids
    if (in_array($data['userid'], $data['user_ids'])) {
        logger("ERROR", "Попытка удаления собственного аккаунта.");
        echo json_encode(['success' => false, 'message' => 'Вы не можете удалить свой собственный аккаунт.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $userIds = array_map(function($id) {
        return "'" . pg_escape_string($id) . "'"; // Экранируем и оборачиваем в кавычки
    }, $data['user_ids']);

    $pdo = connectToDatabase();

    try {
        // Логируем список удаляемых пользователей
        logger("INFO", "Попытка удаления пользователей с ID: " . implode(', ', $data['user_ids']));

        // Формируем запрос с текстовыми значениями
        $sql = "DELETE FROM users WHERE userid IN (" . implode(',', $userIds) . ")";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute();

        if ($result) {
            logger("INFO", "Пользователи успешно удалены. Удаленные ID: " . implode(', ', $data['user_ids']));

        // ВОТ ТУТ ВСТАВИТЬ ВЫЗОВ ФУНКЦИИ НА ОТЗЫВ РОЛЕЙ У ПОЛЬЗОВАТЕЛЯ

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