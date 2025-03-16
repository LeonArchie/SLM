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
logger("INFO", "Проверка сессии...");
startSessionIfNotStarted();
logger("INFO", "Сессия активна.");

// Получаем входные данные
logger("INFO", "Получение входных данных...");
$input = file_get_contents('php://input');
$data = json_decode($input, true);
logger("INFO", "Входные данные получены: " . json_encode($data));

// Проверяем, что данные корректны
logger("INFO", "Проверка корректности входных данных...");
if (empty($data['csrf_token']) || empty($data['privileges']) || empty($data['userIDs'])) {
    logger("ERROR", "Недостаточно данных для выполнения запроса.");
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Недостаточно данных для выполнения запроса.']);
    exit();
}
logger("INFO", "Входные данные корректны.");

        // Проверка CSRF-токена
        logger("INFO", "Проверка CSRF-токена.");
        if ($data['csrf_token'] !== $_SESSION['csrf_token']) {
            logger("ERROR", "Неверный CSRF-токен. Ожидался: " . $_SESSION['csrf_token'] . ", получен: " . $data['csrf_token']);
            http_response_code(403); // Forbidden
            echo json_encode(['success' => false, 'message' => 'Неверный CSRF-токен.']);
            exit;
        }

// Подключаемся к базе данных
logger("INFO", "Подключение к базе данных...");
$pdo = connectToDatabase();
logger("INFO", "Подключение к базе данных успешно.");

try {
    // Начинаем транзакцию
    logger("INFO", "Начало транзакции...");
    $pdo->beginTransaction();
    logger("INFO", "Транзакция начата.");

    // Логируем начало обработки
    logger("INFO", "Начало обработки запроса на снятие привилегий.");

    // Перебираем всех пользователей
    logger("INFO", "Перебор пользователей...");
    foreach ($data['userIDs'] as $userID) {
        logger("INFO", "Обработка пользователя: $userID");

        // Перебираем все привилегии
        logger("INFO", "Перебор привилегий для пользователя $userID...");
        foreach ($data['privileges'] as $privilegeID) {
            logger("INFO", "Обработка привилегии: $privilegeID для пользователя $userID");

            // Удаляем привилегию у пользователя
            logger("INFO", "Удаление привилегии $privilegeID у пользователя $userID...");
            $stmt = $pdo->prepare("
                DELETE FROM privileges 
                WHERE userid = :userid AND id_privileges = :privilegeID
            ");
            $stmt->execute([
                'userid' => $userID,
                'privilegeID' => $privilegeID
            ]);

            // Логируем результат
            if ($stmt->rowCount() > 0) {
                logger("INFO", "Привилегия $privilegeID успешно снята у пользователя $userID.");
            } else {
                logger("INFO", "Привилегия $privilegeID не была назначена пользователю $userID.");
            }
        }
    }

    // Завершаем транзакцию
    logger("INFO", "Завершение транзакции...");
    $pdo->commit();
    logger("INFO", "Транзакция завершена успешно.");

    // Отправляем успешный ответ
    logger("INFO", "Отправка успешного ответа...");
    echo json_encode(['success' => true, 'message' => 'Привилегии успешно сняты.']);
    logger("INFO", "Успешный ответ отправлен.");
} catch (PDOException $e) {
    // Откатываем транзакцию в случае ошибки
    logger("ERROR", "Ошибка при выполнении транзакции: " . $e->getMessage());
    $pdo->rollBack();
    logger("INFO", "Транзакция откачена.");

    // Логируем ошибку
    logger("ERROR", "Ошибка при снятии привилегий: " . $e->getMessage());

    // Отправляем ошибку пользователю
    logger("INFO", "Отправка ошибки пользователю...");
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера при снятии привилегий.']);
    logger("INFO", "Ошибка отправлена.");
}
?>