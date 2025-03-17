<?php
    // Проверяем, определена ли константа ROOT_PATH. Если нет, определяем её как корневой путь документа.
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
    }

    // Формируем путь к файлу function.php, который находится в папке include.
    $file_path = ROOT_PATH . '/include/function.php';

    // Проверяем, существует ли файл function.php. Если нет, выводим сообщение об ошибке и завершаем выполнение скрипта.
    if (!file_exists($file_path)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка 0045: Ошибка сервера.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Подключаем файл function.php.
    require_once $file_path;

    // Запускаем сессию, если она ещё не была запущена.
    startSessionIfNotStarted();

    // Получаем сырые данные из тела запроса.
    $rawData = file_get_contents('php://input');
    if (!$rawData) {
        // Если данные отсутствуют, логируем ошибку и выводим сообщение об ошибке.
        logger("ERROR", "Пустой запрос.");
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка 0046: Ошибка сервера.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Декодируем JSON-данные в ассоциативный массив.
    $data = json_decode($rawData, true);

    // Проверяем, есть ли ошибки при декодировании JSON.
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Если есть ошибки, логируем их и выводим сообщение об ошибке.
        logger("ERROR", "Неверный формат JSON.");
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка 0047: Ошибка сервера.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Проверяем, все ли обязательные поля присутствуют в данных.
    if (empty($data['csrf_token']) || empty($data['admin_userid']) || empty($data['userid']) || empty($data['current_password']) || empty($data['new_password'])) {
        // Если какие-то поля отсутствуют, логируем ошибку и выводим сообщение об ошибке.
        logger("ERROR", "Отсутствуют обязательные данные.");
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ошибка 0048: Необходимо заполнить все поля.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    logger("INFO", "Получен запрос на изменение пароля пользователя" . $data['userid'] . "от администратора" . $data['admin_userid']);
    audit("INFO", "Получен запрос на изменение пароля пользователя" . $data['userid'] . "от администратора" . $data['admin_userid']);

    // Очищаем и экранируем строковые данные, кроме паролей.
    $csrf_token = htmlspecialchars(trim($data['csrf_token']));
    $admin_userid = htmlspecialchars(trim($data['admin_userid']));
    $userid = htmlspecialchars(trim($data['userid']));

    // Пароли не экранируем, так как это может изменить их содержимое.
    $current_password = trim($data['current_password']);
    $new_password = trim($data['new_password']);

    // Логируем начало проверки CSRF-токена.
    logger("INFO", "Начало проверки CSRF-токена для администратора $admin_userid.");
    // Проверяем, совпадает ли CSRF-токен из запроса с токеном в сессии.
    if ($csrf_token !== $_SESSION['csrf_token']) {
        // Если токены не совпадают, логируем ошибку и выводим сообщение об ошибке.
        logger("ERROR", "CSRF-токен не совпадает с ожидаемым значением для администратора $admin_userid.");
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ошибка 0049: Обновите страницу и повторите попытку.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Валидация нового пароля.
    $validationIssues = [];
    if (mb_strlen($new_password, 'UTF-8') < 10) {
        $issue = 'Пароль слишком короткий (минимум 10 символов).';
        $validationIssues[] = $issue;
        logger("WARNING", "Пользователь $userid отправил слишком короткий пароль.");
    } elseif ($new_password === $userid) {
        $issue = 'Пароль не должен совпадать с логином.';
        $validationIssues[] = $issue;
        logger("WARNING", "Пользователь $userid установил пароль, совпадающий с логином.");
    }

    // Если есть ошибки валидации, возвращаем их.
    if (!empty($validationIssues)) {
        http_response_code(400);
        logger("WARNING", "Ошибка изменения пароля пользователя" . $data['userid'] . "Причина:" . $validationIssues);
        audit("IWARNINGNFO", "Ошибка изменения пароля пользователя" . $data['userid'] . "Причина:" . $validationIssues);
        echo json_encode(['success' => false, 'message' => 'Ошибка 0050: Ошибка валидации пароля.', 'issues' => $validationIssues], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Пытаемся подключиться к базе данных.
    try {
        $pdo = connectToDatabase();
    } catch (PDOException $e) {
        // Если подключение не удалось, логируем ошибку и выводим сообщение об ошибке.
        logger("ERROR", "Ошибка подключения к базе данных: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка 0051: Ошибка сервера.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Подготавливаем запрос для получения хэша пароля администратора.
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE userid = :admin_userid");
    $stmt->execute(['admin_userid' => $admin_userid]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    // Проверяем, найден ли администратор в базе данных.
    if (!$admin) {
        // Если администратор не найден, логируем ошибку и выводим сообщение об ошибке.
        logger("ERROR", "Администратор $admin_userid не найден.");
        audit("ERROR", "Администратор $admin_userid не найден.");
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Ошибка 0052: Ошибка сервера.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Проверяем, совпадает ли текущий пароль администратора с хэшем в базе данных.
    if (!password_verify($current_password, $admin['password_hash'])) {
        // Если пароль не совпадает, логируем ошибку и выводим сообщение об ошибке.
        logger("ERROR", "Неверный пароль администратора $admin_userid.");
        audit("ERROR", "Неверный пароль администратора $admin_userid.");
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Ошибка 0053: Неверный пароль администратора.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Хэшируем новый пароль.
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

    // Используем транзакцию для обновления пароля.
    try {
        $pdo->beginTransaction();

        // Подготавливаем запрос для обновления пароля пользователя.
        $stmt = $pdo->prepare("UPDATE users SET password_hash = :password_hash WHERE userid = :userid");
        $stmt->execute(['password_hash' => $new_password_hash, 'userid' => $userid]);

        // Фиксируем транзакцию.
        $pdo->commit();

        // Логируем успешное обновление пароля.
        logger("INFO", "Пароль пользователя $userid успешно обновлён администратором $admin_userid.");
        audit("INFO", "Пароль пользователя $userid успешно обновлён администратором $admin_userid.");
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Ошибка 0054: Пароль пользователя успешно изменён.'], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        // Откатываем транзакцию в случае ошибки.
        $pdo->rollBack();

        // Логируем ошибку.
        logger("ERROR", "Ошибка при обновлении пароля пользователя $userid: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка 0055: Ошибка сервера.'], JSON_UNESCAPED_UNICODE);
    }
?>