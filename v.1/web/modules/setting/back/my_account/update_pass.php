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
        echo json_encode(['success' => false, 'message' => 'Ошибка 0018: Ошибка сервера.'], JSON_UNESCAPED_UNICODE);
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
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ошибка 0019: Пустой запрос.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Декодируем JSON-данные в ассоциативный массив.
    $data = json_decode($rawData, true);

    // Проверяем, есть ли ошибки при декодировании JSON.
    if (json_last_error() !== JSON_ERROR_NONE) {
        logger("ERROR", "Неверный формат JSON.");
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка 0020: Ошибка сервера.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Проверяем, переданы ли обязательные поля: current_password и new_password.
    if (empty($data['current_password']) || empty($data['new_password'])) {
        logger("ERROR", "Отсутствуют обязательные данные.");
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ошибка 0021: Необходимо заполнить все поля.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Проверяем, передан ли CSRF-токен.
    if (empty($data['csrf_token'])) {
        logger("ERROR", "CSRF-токен не передан в данных.");
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ошибка 0022: Ошибка безопасности: CSRF-токен не передан.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Проверяем, совпадает ли переданный CSRF-токен с токеном, хранящимся в сессии.
    if ($data['csrf_token'] !== $_SESSION['csrf_token']) {
        logger("ERROR", "CSRF-токен не совпадает с ожидаемым значением.");
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ошибка 0023: Ошибка безопасности: неверный CSRF-токен.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Убираем лишние пробелы из текущего и нового пароля.
    $current_password = trim($data['current_password']);
    $new_password = trim($data['new_password']);

    // Валидация нового пароля.
    $validationIssues = [];
    if (mb_strlen($new_password, 'UTF-8') < 10) {
        $issue = 'Пароль слишком короткий (минимум 10 символов).';
        $validationIssues[] = $issue;
        logger("WARNING", "Пользователь отправил слишком короткий пароль.");
    } elseif ($new_password === $_SESSION['userlogin']) {
        $issue = 'Пароль не должен совпадать с логином.';
        $validationIssues[] = $issue;
        logger("WARNING", "Пользователь установил пароль, совпадающий с логином.");
    }

    // Проверка на совпадение текущего и нового пароля.
    if ($current_password === $new_password) {
        $issue = 'Новый пароль должен отличаться от текущего.';
        $validationIssues[] = $issue;
        logger("WARNING", "Пользователь попытался установить пароль, совпадающий с текущим.");
    }

    // Проверка длины пароля (максимум 72 символа).
    if (strlen($new_password) > 72) {
        $issue = 'Пароль слишком длинный (максимум 72 символа).';
        $validationIssues[] = $issue;
        logger("WARNING", "Пользователь отправил слишком длинный пароль.");
    }

    // Если есть ошибки валидации, возвращаем их.
    if (!empty($validationIssues)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ошибка 0027: Ошибка валидации пароля.', 'issues' => $validationIssues], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Подключаемся к базе данных.
    $pdo = connectToDatabase();

    // Получаем ID пользователя из сессии.
    $userId = $_SESSION['userid'];

    // Подготавливаем запрос для получения хэша пароля пользователя.
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE userid = :userid");
    $stmt->execute(['userid' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Проверяем, найден ли пользователь в базе данных.
    if (!$user) {
        logger("ERROR", "Пользователь с ID $userId не найден.");
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ошибка 0024: Пользователь не найден.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Проверяем, совпадает ли текущий пароль с хэшем, хранящимся в базе данных.
    if (!password_verify($current_password, $user['password_hash'])) {
        logger("ERROR", "Неверный текущий пароль для пользователя с ID $userId.");
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ошибка 0025: Неверный текущий пароль.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Хэшируем новый пароль.
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

    // Подготавливаем запрос для обновления пароля пользователя.
    $stmt = $pdo->prepare("UPDATE users SET password_hash = :password_hash WHERE userid = :userid");
    $result = $stmt->execute(['password_hash' => $new_password_hash, 'userid' => $userId]);

    // Проверяем, успешно ли обновлён пароль.
    if ($result) {
        logger("INFO", "Пароль успешно обновлен для пользователя с ID $userId.");
        audit("INFO", "Пароль успешно обновлен для пользователя с ID $userId.");
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Пароль успешно изменен.'], JSON_UNESCAPED_UNICODE);
    } else {
        logger("ERROR", "Ошибка при обновлении пароля для пользователя с ID $userId.");
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка 0026: Ошибка при изменении пароля.'], JSON_UNESCAPED_UNICODE);
    }
?>