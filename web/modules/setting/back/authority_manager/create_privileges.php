<?php
    define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
    $file_path = ROOT_PATH . '/include/function.php';

    // Проверяем существование файла function.php
    if (!file_exists($file_path)) {
        echo json_encode(['success' => false, 'message' => 'Ошибка сервера: файл function.php не найден.']);
        exit();
    }

    require_once $file_path;

    // Логирование начала выполнения скрипта
    logger("INFO", "Начало выполнения скрипта save_user_data.php.");

        startSessionIfNotStarted();

// Функция для логирования (предполагается, что она уже реализована)
function logger($level, $message) {
    // Пример логирования в файл
    $logFile = __DIR__ . '/app.log';
    $logMessage = "[" . date('Y-m-d H:i:s') . "] [$level] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

НЕТ ПРОВЕРКИ ТОКЕНА НЕТ ПРОВЕРКИ ТОКЕНА НЕТ ПРОВЕРКИ ТОКЕНА НЕТ ПРОВЕРКИ ТОКЕНА НЕТ ПРОВЕРКИ ТОКЕНА НЕТ ПРОВЕРКИ ТОКЕНА НЕТ ПРОВЕРКИ ТОКЕНА

// Основной код скрипта
try {
    // Подключение к базе данных
    $pdo = connectToDatabase();

    // Получение данных из тела запроса
    $data = json_decode(file_get_contents('php://input'), true);

    // Проверка наличия обязательных данных
    if (empty($data['csrf_token']) || empty($data['privilegeName']) || empty($data['privilegeID'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Недостаточно данных для выполнения запроса.']);
        exit;
    }

    // Проверка CSRF-токена
    if (!validateCSRFToken($data['csrf_token'])) {
        http_response_code(403); // Forbidden
        echo json_encode(['success' => false, 'message' => 'Неверный CSRF-токен.']);
        exit;
    }

    // Проверка уникальности privilegeID
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM name_privileges WHERE id_privileges = ?");
    $stmt->execute([$data['privilegeID']]);
    if ($stmt->fetchColumn() > 0) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Привилегия с таким ID уже существует.']);
        exit;
    }

    // Вставка новой записи в таблицу name_privileges
    $insertStmt = $pdo->prepare("INSERT INTO name_privileges (id, name_privileges, id_privileges, pages) VALUES (?, ?, ?, ?)");
    $insertStmt->execute([
        csrf_token(), // Генерация уникального ID
        $data['privilegeName'],
        $data['privilegeID'],
        $data['pagesCheckbox'] // Передаем boolean напрямую
    ]);

    // Логирование успешного создания привилегии
    logger("INFO", "Новая привилегия успешно создана: " . $data['privilegeName']);

    // Возвращаем успешный ответ
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    // Логирование ошибки базы данных
    logger("ERROR", "Ошибка базы данных: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных.']);
} catch (Exception $e) {
    // Логирование других ошибок
    logger("ERROR", "Ошибка: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Внутренняя ошибка сервера.']);
}
?>