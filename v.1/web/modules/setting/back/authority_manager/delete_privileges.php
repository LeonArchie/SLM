<?php
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
    }
    $file_path = ROOT_PATH . '/include/function.php';

    // Проверяем существование файла function.php
    if (!file_exists($file_path)) {
        echo json_encode(['success' => false, 'message' => 'Ошибка сервера: файл function.php не найден.']);
        exit();
    }

    require_once $file_path;

    startSessionIfNotStarted();

    // Подключение к базе данных
    $pdo = connectToDatabase();

    // Функция для проверки CSRF-токена
    function validateCsrfToken($token) {
        if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $token) {
            return false;
        }
        return true;
    }

    // Получение данных из запроса
    $input = json_decode(file_get_contents('php://input'), true);

    // Проверка наличия CSRF-токена
    if (!isset($input['csrf_token']) || !validateCsrfToken($input['csrf_token'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ошибка CSRF-токена']);
        logger("ERROR", "Ошибка CSRF-токена");
        exit;
    }

    // Проверка наличия привилегий для удаления
    if (!isset($input['privileges']) || empty($input['privileges'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Привилегии для удаления не указаны']);
        logger("ERROR", "Привилегии для удаления не указаны");
        exit;
    }

    try {
        // Удаление записей из таблицы privileges
        $stmt = $pdo->prepare("DELETE FROM privileges WHERE id_privileges = :id");
        foreach ($input['privileges'] as $id) {
            $stmt->execute(['id' => $id]);
        }

        // Удаление записей из таблицы name_privileges
        $stmt = $pdo->prepare("DELETE FROM name_privileges WHERE id_privileges = :id");
        foreach ($input['privileges'] as $id) {
            $stmt->execute(['id' => $id]);
        }

        // Логирование успешного завершения
        logger("INFO", "Привилегии успешно удалены");

        // Отправка успешного ответа
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        // Логирование ошибки
        logger("ERROR", "Ошибка при удалении привилегий: " . $e->getMessage());

        // Отправка ошибки
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка при удалении привилегий']);
    }
?>