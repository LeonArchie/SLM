<?php
    // Проверяем, определена ли константа ROOT_PATH, если нет, то определяем её как корневую директорию сервера
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
    }

    // Формируем путь к файлу function.php, который находится в папке include
    $file_path = ROOT_PATH . '/include/function.php';

    // Проверяем, существует ли файл function.php
    if (!file_exists($file_path)) {
        // Устанавливаем HTTP-код 500 (Internal Server Error)
        http_response_code(500);
        // Возвращаем JSON-ответ с ошибкой и завершаем выполнение скрипта
        echo json_encode(['success' => false, 'message' => 'Ошибка 0082: Ошибка сервера.']);
        exit();
    }

    // Подключаем файл function.php
    require_once $file_path;
        
    
    $file_path = ROOT_PATH . '/modules/setting/include/all_accounts.php';

    // Проверяем существование файла function.php
    if (!file_exists($file_path)) {
        logger("ERROR", "Файл all_accounts.php не найден.");
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'message' => 'Ошибка 0129: Ошибка сервера.']);
        exit();
    }
    
    require_once $file_path;
    
    // Вызываем функцию startSessionIfNotStarted, которая запускает сессию, если она ещё не была запущена
    startSessionIfNotStarted();

    // Получаем данные, переданные в теле запроса, и декодируем их из JSON в ассоциативный массив
    $data = json_decode(file_get_contents('php://input'), true);

    // Проверяем, передан ли CSRF-токен и совпадает ли он с токеном, хранящимся в сессии
    if (empty($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
        // Устанавливаем HTTP-код 403 (Forbidden)
        http_response_code(403);
        // Логируем ошибку и возвращаем JSON-ответ с сообщением об ошибке
        logger("ERROR", "Неверный CSRF-токен.");
        echo json_encode(['success' => false, 'message' => 'Ошибка 0083: Ошибка сервера.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Проверяем, передан ли массив user_ids и является ли он массивом
    if (empty($data['user_ids']) || !is_array($data['user_ids'])) {
        // Устанавливаем HTTP-код 400 (Bad Request)
        http_response_code(400);
        // Логируем ошибку и возвращаем JSON-ответ с сообщением об ошибке
        logger("ERROR", "Отсутствуют или некорректные ID пользователей.");
        echo json_encode(['success' => false, 'message' => 'Ошибка 0084: Ошибка сервера.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    logger("INFO", "Получен запрос на удаление пользователя $data");
    audit("INFO", "Получен запрос на удаление пользователя $data");

    // Проверяем, не содержится ли ID текущего пользователя в массиве user_ids
    if (in_array($data['userid'], $data['user_ids'])) {
        // Устанавливаем HTTP-код 400 (Bad Request)
        http_response_code(400);
        // Логируем ошибку и возвращаем JSON-ответ с сообщением об ошибке
        logger("ERROR", "Попытка удаления собственного аккаунта.");
        audit("ERROR", "Попытка удаления собственного аккаунта.");
        echo json_encode(['success' => false, 'message' => 'Ошибка 0085: Вы не можете удалить свой собственный аккаунт.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Подключаемся к базе данных
    $pdo = connectToDatabase();

    try {
        // Логируем список ID пользователей, которых пытаемся удалить
        logger("INFO", "Попытка удаления пользователей с ID: " . implode(', ', $data['user_ids']));
        audit("INFO", "Попытка удаления пользователей с ID: " . implode(', ', $data['user_ids']));

        // Вызываем функцию для удаления пользователей
        $result = deleteUsers($pdo, $data['user_ids']);

        // Проверяем, успешно ли выполнен запрос на удаление
        if ($result) {
            // Устанавливаем HTTP-код 200 (OK)
            http_response_code(200);
            // Логируем успешное удаление и возвращаем JSON-ответ с сообщением об успехе
            logger("INFO", "Пользователи успешно удалены. Удаленные ID: " . implode(', ', $data['user_ids']));
            audit("INFO", "Пользователи успешно удалены. Удаленные ID: " . implode(', ', $data['user_ids']));

            echo json_encode(['success' => true, 'message' => 'Пользователи успешно удалены.'], JSON_UNESCAPED_UNICODE);
        } else {
            // Устанавливаем HTTP-код 500 (Internal Server Error)
            http_response_code(500);
            // Логируем ошибку и возвращаем JSON-ответ с сообщением об ошибке
            logger("ERROR", "Ошибка при удалении пользователей.");
            echo json_encode(['success' => false, 'message' => 'Ошибка 0085: Произошла неизвестная ошибка.'], JSON_UNESCAPED_UNICODE);
        }
    } catch (Exception $e) {
        // Устанавливаем HTTP-код 500 (Internal Server Error)
        http_response_code(500);
        // Логируем исключение и возвращаем JSON-ответ с сообщением об ошибке
        logger("ERROR", "Исключение при удалении пользователей: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Ошибка 0086: Произошла неизвестная ошибка.'], JSON_UNESCAPED_UNICODE);
    }
?>