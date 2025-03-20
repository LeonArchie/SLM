<?php
    // Проверяем, определена ли константа ROOT_PATH, если нет, определяем её как корневой путь сервера
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
    }

    // Формируем путь к файлу function.php
    $file_path = ROOT_PATH . '/include/function.php';

    // Проверяем, существует ли файл function.php
    if (!file_exists($file_path)) {
        // Если файл не найден, возвращаем ошибку и завершаем выполнение
        http_response_code(500); // Внутренняя ошибка сервера
        echo json_encode(['success' => false, 'message' => 'Ошибка 0074: Ошибка сервера.']);
        exit();
    }

    // Подключаем файл function.php
    require_once $file_path;

    // Запускаем сессию, если она ещё не запущена
    startSessionIfNotStarted();
    
    $file_path = ROOT_PATH . '/modules/setting/include/all_accounts.php';

    // Проверяем существование файла 
    if (!file_exists($file_path)) {
        logger("ERROR", "Файл all_accounts не найден.");
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'message' => 'Ошибка 0127: Ошибка сервера.']);
        exit();
    }
    
    require_once $file_path;

    $file_path = ROOT_PATH . '/modules/setting/include/authority_manager.php';

    // Проверяем существование файла
    if (!file_exists($file_path)) {
        logger("ERROR", "Файл authority_manager.php не найден.");
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'message' => 'Ошибка 0128: Ошибка сервера.']);
        exit();
    }

    require_once $file_path;

    // Подключение файла template.json
    $templatePath = TEMPLATE;
    if (!file_exists($templatePath)) {
        logger("ERROR", "Файл template.json не найден.");
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'message' => 'Ошибка 0130: Ошибка сервера.'], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    $templateData = json_decode(file_get_contents($templatePath), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        logger("ERROR", "Неверный формат JSON в файле template.json.");
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'message' => 'Ошибка 0131: Ошибка сервера.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Чтение входящих данных как JSON
    $data = file_get_contents('php://input');
    if (!$data) {
        // Если данные пустые, логируем ошибку и возвращаем сообщение об ошибке
        http_response_code(400); // Неверный запрос
        logger("ERROR", "Пустой запрос.");
        echo json_encode(['success' => false, 'message' => 'Ошибка 0075: Пустой запрос.'], JSON_UNESCAPED_UNICODE);
        exit();
    }
        
    // Декодируем JSON данные
    $data = json_decode($data, true);
        
    // Проверяем корректность JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Если JSON некорректен, логируем ошибку и возвращаем сообщение об ошибке
        http_response_code(400); // Неверный запрос
        logger("ERROR", "Неверный формат JSON.");
        echo json_encode(['success' => false, 'message' => 'Ошибка 0076: Ошибка сервера.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    logger("INFO", "Получен запрос на создание пользователя " . json_encode($data));
    audit("INFO", "Получен запрос на создание пользователя " . json_encode($data));

    // Проверка CSRF-токена
    if (empty($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
        // Если токен не совпадает, логируем ошибку и возвращаем сообщение об ошибке
        http_response_code(403); // Запрещено (ошибка безопасности)
        logger("ERROR", "Ошибка безопасности: неверный CSRF-токен.");
        echo json_encode(['success' => false, 'message' => 'Ошибка 0077: Обновите страницу и повторите попытку.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Валидация входных данных
    $validationIssues = validateInputData($data);
    if (!empty($validationIssues)) {
        // Если есть ошибки валидации, логируем их и возвращаем сообщение об ошибке
        http_response_code(400); // Неверный запрос
        array_unshift($validationIssues, "Ошибка 0078:");
        $errorMessage = implode(' ', $validationIssues);
        logger("ERROR", "Ошибка валидации: " . $errorMessage);
        echo json_encode(['success' => false, 'message' => $errorMessage], JSON_UNESCAPED_UNICODE);
        exit();
    }

    try {
        // Подключение к базе данных
        $pdo = connectToDatabase();
    } catch (PDOException $e) {
        // Логируем ошибку и возвращаем сообщение об ошибке
        http_response_code(500); // Внутренняя ошибка сервера
        logger("ERROR", "Ошибка подключения к базе данных: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Ошибка 0079: Ошибка сервера.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Создание пользователя
    $result = createUser($pdo, $data);
    if (!$result['success']) {
        http_response_code($result['http_code']);
        echo json_encode(['success' => false, 'message' => $result['message']], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Получаем имя массива из $data['role']
    $roleKey = $data['role'];

    // Проверяем, существует ли массив с таким именем в template.json
    if (!isset($templateData[$roleKey])) {
        logger("ERROR", "Роль '$roleKey' не найдена в template.json.");
        http_response_code(400); // Неверный запрос
        echo json_encode(['success' => false, 'message' => "Ошибка 0132: Роль '$roleKey' не найдена."], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Получаем массив привилегий для указанной роли
    $privileges = $templateData[$roleKey]['value'];

    // Получение userID по userLogin через прямой запрос к базе данных
    $userLogin = $data['userlogin'];
    try {
        $stmt = $pdo->prepare("SELECT userid FROM users WHERE userlogin = :userlogin");
        $stmt->execute(['userlogin' => $userLogin]);
        $userID = $stmt->fetchColumn(); // Получаем значение userID

        if (!$userID) {
            logger("ERROR", "Пользователь с логином '$userLogin' не найден.");
            http_response_code(404); // Не найдено
            echo json_encode(['success' => false, 'message' => "Ошибка 0133: Пользователь с логином '$userLogin' не найден."], JSON_UNESCAPED_UNICODE);
            exit();
        }
    } catch (PDOException $e) {
        logger("ERROR", "Ошибка при поиске пользователя: " . $e->getMessage());
        http_response_code(500); // Внутренняя ошибка сервера
        echo json_encode(['success' => false, 'message' => 'Ошибка 0134: Ошибка сервера.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Назначение привилегий
    try {
        assignPrivileges($pdo, [$userID], $privileges);
        logger("INFO", "Привилегии успешно назначены пользователю '$userLogin'.");
        echo json_encode(['success' => true, 'message' => 'Пользователь успешно создан и роли назначены.'], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        logger("ERROR", "Ошибка назначения привилегий: " . $e->getMessage());
        echo json_encode(['success' => true, 'message' => 'Пользователь успешно создан, но роли не назначены.'], JSON_UNESCAPED_UNICODE);
    }
?>