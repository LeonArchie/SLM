<?php
    define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
    $file_path = ROOT_PATH . '/include/function.php';

    // Проверяем существование файла function.php
    if (!file_exists($file_path)) {
        echo json_encode(['success' => false, 'message' => 'Ошибка сервера: файл function.php не найден.']);
        exit();
    }

    require_once $file_path;

    logger("INFO", "Начало выполнения скрипта blockuser.php.");

    startSessionIfNotStarted();


    // Получаем данные из тела запроса
    $data = json_decode(file_get_contents('php://input'), true);

    //logger("DEBUG", "Полученные данные: " . print_r($data, true)); // Логирование полученных данных

    // Проверка наличия всех необходимых параметров
    if (empty($data['csrf_token']) || empty($data['userid']) || empty($data['user_ids'])) {
        logger("ERROR", "Отсутствуют обязательные параметры.");
        echo json_encode(['success' => false, 'message' => 'Отсутствуют обязательные параметры.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Проверка CSRF-токена
    if ($data['csrf_token'] !== $_SESSION['csrf_token']) {
        logger("ERROR", "Неверный CSRF-токен.");
        echo json_encode(['success' => false, 'message' => 'Ошибка безопасности.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Проверка, что userid не входит в список user_ids
    if (in_array($data['userid'], $data['user_ids'])) {
        logger("ERROR", "Попытка заблокировать самого себя.");
        echo json_encode(['success' => false, 'message' => 'Нельзя заблокировать самого себя.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Подключение к базе данных
    $pdo = connectToDatabase();
    //logger("DEBUG", "Успешное подключение к базе данных.");

    try {
        // Начинаем транзакцию
        $pdo->beginTransaction();

        // Проходим по каждому user_id и меняем значение active
        foreach ($data['user_ids'] as $userId) {

            // Получаем текущее значение active
            $stmt = $pdo->prepare("SELECT active FROM users WHERE userid = :userid");
            $stmt->execute(['userid' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                logger("ERROR", "Пользователь с ID $userId не найден.");
                continue; // Пропускаем, если пользователь не найден
            }

            // Меняем значение active на противоположное
            $newActiveValue = $user['active'] ? 'false' : 'true'; // Для PostgreSQL

            // Обновляем значение active
            $updateStmt = $pdo->prepare("UPDATE users SET active = :active WHERE userid = :userid");
            $updateStmt->execute([
                'active' => $newActiveValue,
                'userid' => $userId
            ]);

            logger("INFO", "Статус пользователя $userId изменен на " . ($newActiveValue === 'true' ? 'активен' : 'заблокирован') . ".");
        }

        // Фиксируем транзакцию
        $pdo->commit();

        logger("INFO", "Операция блокировки/разблокировки завершена успешно.");
        echo json_encode(['success' => true, 'message' => 'Операция завершена успешно.'], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        // Откатываем транзакцию в случае ошибки
        $pdo->rollBack();

        logger("ERROR", "Исключение при блокировке пользователей: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Произошла ошибка при блокировке пользователей.'], JSON_UNESCAPED_UNICODE);
    }
?>