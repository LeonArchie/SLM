<?php
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
    }
    $file_path = ROOT_PATH . '/include/function.php';

    // Проверяем существование файла function.php
    if (!file_exists($file_path)) {
        logger("ERROR", "Файл function.php не найден.");
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'message' => 'Ошибка 0106: Ошибка сервера.']);
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
    startSessionIfNotStarted();

    // Получаем входные данные
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    logger("INFO", "Получен запрос на добавление привилегий. $data");
    audit("INFO", "Получен запрос на добавление привилегий. $data");

    // Проверяем, что данные корректны
    if (empty($data['csrf_token']) || empty($data['privileges']) || empty($data['userIDs'])) {
        logger("ERROR", "Недостаточно данных для выполнения запроса.");
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Ошибка 0104: Отсутствуют обязательные параметры.']);
        exit();
    }

    // Проверяем CSRF-токен на соответствие токену, хранящемуся в сессии
    logger("INFO", "Проверка CSRF-токена.");
    if ($data['csrf_token'] !== $_SESSION['csrf_token']) {
        // Если токены не совпадают, логируем ошибку и возвращаем JSON-ответ с сообщением об ошибке
        logger("ERROR", "Неверный CSRF-токен.");
        http_response_code(403); // Устанавливаем код ответа 403 (Forbidden)
        echo json_encode(['success' => false, 'message' => 'Ошибка 0111: Обновите страницу и повторите попытку.']);
        exit; // Прекращаем выполнение скрипта
    }

    // Подключаемся к базе данных
    $pdo = connectToDatabase();

    try {
        // Логируем начало обработки
        logger("INFO", "Начало обработки запроса на назначение привилегий.");

        // Вызываем функцию для назначения привилегий
        assignPrivileges($pdo, $data['userIDs'], $data['privileges']);

        // Успешный ответ
        http_response_code(200); // OK
        echo json_encode(['success' => true, 'message' => 'Привилегии успешно назначены.']);
    } catch (Exception $e) {
        // Логируем ошибку
        logger("ERROR", "Ошибка при назначении привилегий: " . $e->getMessage());

        // Ошибка сервера
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'message' => 'Ошибка 0105: Произошла неизвестная ошибка.']);
    }
?>