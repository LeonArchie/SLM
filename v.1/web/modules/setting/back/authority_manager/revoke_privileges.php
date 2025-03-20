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


    $file_path = ROOT_PATH . '/modules/setting/include/authority_manager.php';

    // Проверяем существование файла function.php
    if (!file_exists($file_path)) {
        logger("ERROR", "Файл authority_manager.php не найден.");
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'message' => 'Ошибка 0107: Ошибка сервера.']);
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
        // Логируем начало обработки
        logger("INFO", "Начало обработки запроса на снятие привилегий.");

        // Вызываем функцию для снятия привилегий
        $exception = revokePrivileges($pdo, $data['userIDs'], $data['privileges']);

        // Если функция вернула исключение, обрабатываем его
        if ($exception instanceof PDOException) {
            throw $exception; // Пробрасываем исключение для обработки в catch-блоке
        }

        // Отправляем успешный ответ
        logger("INFO", "Отправка успешного ответа...");
        echo json_encode(['success' => true, 'message' => 'Привилегии успешно сняты.']);
        logger("INFO", "Успешный ответ отправлен.");
    } catch (PDOException $e) {
        // Логируем ошибку
        logger("ERROR", "Ошибка при снятии привилегий: " . $e->getMessage());

        // Отправляем ошибку пользователю
        logger("INFO", "Отправка ошибки пользователю...");
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'message' => 'Ошибка сервера при снятии привилегий.']);
        logger("INFO", "Ошибка отправлена.");
    }

?>