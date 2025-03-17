<?php
    // Проверка и определение ROOT_PATH
    // Если константа ROOT_PATH не определена, задаем её как корневой путь сервера.
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
    }

    // Подключение function.php
    // Формируем путь к файлу function.php, который находится в папке include.
    $file_path = ROOT_PATH . '/include/function.php';

    // Проверяем, существует ли файл function.php. Если нет, возвращаем ошибку и завершаем выполнение.
    if (!file_exists($file_path)) {
        http_response_code(500); // Устанавливаем HTTP-код 500 (Internal Server Error).
        echo json_encode(['success' => false, 'message' => 'Ошибка 0067: Ошибка сервера.']);
        exit();
    }

    // Подключаем файл function.php, который содержит необходимые функции.
    require_once $file_path;

    // Запуск сессии
    // Если сессия не была запущена, запускаем её.
    startSessionIfNotStarted();

    // Получение данных
    // Читаем данные из тела запроса и декодируем их из JSON в ассоциативный массив.
    $data = json_decode(file_get_contents('php://input'), true);

    // Проверяем, произошла ли ошибка при декодировании JSON.
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Логируем ошибку и выводим сообщение об ошибке в формате JSON.
        logger("ERROR", "Ошибка при декодировании JSON: " . json_last_error_msg());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка 0073: Ошибка сервера.']);
        exit();
    }

    logger("INFO", "Получен запрос для блокировки профиля пользователя. Полученные данные: $data");
    audit("INFO", "Получен запрос для блокировки профиля пользователя. Полученные данные: $data");

    // Проверка наличия обязательных параметров
    // Проверяем, что все обязательные параметры (csrf_token, userid, user_ids) присутствуют.
    if (empty($data['csrf_token']) || empty($data['userid']) || empty($data['user_ids'])) {
        logger("ERROR", "Отсутствуют обязательные параметры."); // Логируем ошибку.
        http_response_code(400); // Устанавливаем HTTP-код 400 (Bad Request).
        echo json_encode(['success' => false, 'message' => 'Ошибка 0068: Отсутствуют обязательные параметры.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Проверка CSRF-токена
    // Сравниваем CSRF-токен из запроса с токеном, хранящимся в сессии.
    if ($data['csrf_token'] !== $_SESSION['csrf_token']) {
        logger("ERROR", "Неверный CSRF-токен."); // Логируем ошибку.
        http_response_code(403); // Устанавливаем HTTP-код 403 (Forbidden).
        echo json_encode(['success' => false, 'message' => 'Ошибка 0069: Обновите страницу и повторите попытку.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Проверка, что userid не входит в список user_ids
    // Проверяем, что пользователь не пытается заблокировать самого себя.
    if (in_array($data['userid'], $data['user_ids'])) {
        logger("ERROR", "Попытка пользователем " . $data['userid'] . "заблокировать самого себя."); // Логируем ошибку.
        audit("ERROR", "Попытка пользователем " . $data['userid'] . "заблокировать самого себя.");
        http_response_code(400); // Устанавливаем HTTP-код 400 (Bad Request).
        echo json_encode(['success' => false, 'message' => 'Ошибка 0070: Нельзя заблокировать самого себя.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Подключение к базе данных
    try {
        // Пытаемся подключиться к базе данных.
        $pdo = connectToDatabase();
    } catch (Exception $e) {
        // Если подключение не удалось, логируем ошибку и завершаем выполнение.
        logger("ERROR", "Ошибка подключения к базе данных: " . $e->getMessage());
        http_response_code(500); // Устанавливаем HTTP-код 500 (Internal Server Error).
        echo json_encode(['success' => false, 'message' => 'Ошибка 0071: Ошибка сервера.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    try {
        // Начало транзакции
        // Начинаем транзакцию для обеспечения атомарности операций.
        $pdo->beginTransaction();

        // Обновление статуса пользователей
        // Проходим по каждому user_id из списка и изменяем его статус.
        foreach ($data['user_ids'] as $userId) {
            // Получение текущего значения active
            // Подготавливаем и выполняем запрос для получения текущего статуса пользователя.
            $stmt = $pdo->prepare("SELECT active FROM users WHERE userid = :userid");
            $stmt->execute(['userid' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Если пользователь не найден, логируем ошибку и переходим к следующему user_id.
            if (!$user) {
                logger("ERROR", "Пользователь с ID $userId не найден.");
                audit("ERROR", "Попытка заблокировать пользователя с несуществующим ID: $userId не найден.");
                continue; // Пропуск, если пользователь не найден
            }

            // Изменение значения active на противоположное
            // Меняем значение active на противоположное (1 на 0 и наоборот).
            $newActiveValue = $user['active'] ? 0 : 1; // Используем числа вместо строк

            // Обновление значения active
            // Подготавливаем и выполняем запрос для обновления статуса пользователя.
            $updateStmt = $pdo->prepare("UPDATE users SET active = :active WHERE userid = :userid");
            $updateStmt->execute([
                'active' => $newActiveValue,
                'userid' => $userId
            ]);

            // Логируем изменение статуса пользователя.
            logger("INFO", "Статус пользователя $userId изменен на " . ($newActiveValue ? 'активен' : 'заблокирован') . ".");
            audit("INFO", "Статус пользователя $userId изменен на " . ($newActiveValue ? 'активен' : 'заблокирован') . ".");
        }

        // Фиксация транзакции
        // Если все операции прошли успешно, фиксируем транзакцию.
        $pdo->commit();

        // Логируем успешное завершение операции.
        logger("INFO", "Операция блокировки/разблокировки завершена успешно.");
        http_response_code(200); // Устанавливаем HTTP-код 200 (OK).
        echo json_encode(['success' => true, 'message' => 'Операция завершена успешно.'], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        // Откат транзакции в случае ошибки
        // Если произошла ошибка, откатываем транзакцию.
        $pdo->rollBack();

        // Логируем ошибку с деталями исключения.
        logger("ERROR", "Исключение: " . $e->getMessage() . "\nТрассировка: " . $e->getTraceAsString());
        http_response_code(500); // Устанавливаем HTTP-код 500 (Internal Server Error).
        echo json_encode(['success' => false, 'message' => 'Ошибка 0072: Произошла неизвестная ошибка.'], JSON_UNESCAPED_UNICODE);
    }
?>