<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
}
$file_path = ROOT_PATH . '/include/function.php';

// Проверяем существование файла function.php
if (!file_exists($file_path)) {
    logger("ERROR", "Файл function.php не найден.");
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера: файл function.php не найден.']);
    exit();
}

require_once $file_path;

// Запуск сессии, если она еще не запущена
startSessionIfNotStarted();

// Получаем входные данные
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Проверяем, что данные корректны
if (empty($data['csrf_token']) || empty($data['privileges']) || empty($data['userIDs'])) {
    logger("ERROR", "Недостаточно данных для выполнения запроса.");
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Недостаточно данных для выполнения запроса.']);
    exit();
}

// Подключаемся к базе данных
$pdo = connectToDatabase();

try {
    // Начинаем транзакцию
    $pdo->beginTransaction();

    // Логируем начало обработки
    logger("INFO", "Обработка запроса на создание привилегий.");

    // Перебираем всех пользователей
    foreach ($data['userIDs'] as $userID) {
        // Перебираем все привилегии
        foreach ($data['privileges'] as $privilegeID) {
            // Проверяем, не назначена ли уже привилегия пользователю
            $stmt = $pdo->prepare("
                SELECT id FROM privileges 
                WHERE userid = :userid AND id_privileges = :privilegeID
            ");
            $stmt->execute(['userid' => $userID, 'privilegeID' => $privilegeID]);
            $existingRecord = $stmt->fetch();

            // Если привилегия уже назначена, пропускаем
            if ($existingRecord) {
                logger("INFO", "Привилегия $privilegeID уже назначена пользователю $userID.");
                continue;
            }

            // Генерируем новый GUID для записи
            $newID = generateGUID();

            // Добавляем привилегию пользователю
            $stmt = $pdo->prepare("
                INSERT INTO privileges (id, userid, id_privileges) 
                VALUES (:id, :userid, :privilegeID)
            ");
            $stmt->execute([
                'id' => $newID,
                'userid' => $userID,
                'privilegeID' => $privilegeID
            ]);

            logger("INFO", "Привилегия $privilegeID назначена пользователю $userID.");
        }
    }

    // Завершаем транзакцию
    $pdo->commit();

    // Отправляем успешный ответ
    echo json_encode(['success' => true, 'message' => 'Привилегии успешно назначены.']);
} catch (PDOException $e) {
    // Откатываем транзакцию в случае ошибки
    $pdo->rollBack();

    // Логируем ошибку
    logger("ERROR", "Ошибка при назначении привилегий: " . $e->getMessage());

    // Отправляем ошибку пользователю
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера при назначении привилегий.']);
}
?>