<?php
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
    }
    $file_path = ROOT_PATH . '/include/function.php';

    if (!file_exists($file_path)) {
        echo json_encode(['success' => false, 'message' => 'Ошибка сервера: файл function.php не найден.']);
        exit();
    }

    require_once $file_path;

    startSessionIfNotStarted();

    // Чтение входящих данных как JSON
    $data = file_get_contents('php://input');
    if (!$data) {
        logger("ERROR", "Пустой запрос.");
        echo json_encode(['success' => false, 'message' => 'Пустой запрос.'], JSON_UNESCAPED_UNICODE);
        exit();
    }
        
    $data = json_decode($data, true);
        
    // Проверка корректности JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        logger("ERROR", "Неверный формат JSON.");
        echo json_encode(['success' => false, 'message' => 'Неверный формат JSON.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Проверка токена
    if (empty($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
        logger("ERROR", "Ошибка безопасности: неверный CSRF-токен.");
        echo json_encode(['success' => false, 'message' => 'Ошибка безопасности: неверный CSRF-токен.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    if (empty($data['full_name']) || empty($data['userlogin']) || empty($data['password']) || empty($data['email'])) {
        logger("ERROR", "Отсутствуют обязательные данные.");
        echo json_encode(['success' => false, 'message' => 'Необходимо заполнить все поля.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $validationIssues = [];

    // Валидация полного ФИО
    if (mb_strlen($data['full_name'], 'UTF-8') > 50) {
        $issue = 'Полное ФИО превышает допустимую длину (максимум 50 символов).';
        $validationIssues[] = $issue;
        logger("WARNING", "Пользователь отправил слишком длинное полное ФИО: " . htmlspecialchars($data['full_name']));
    } elseif (!preg_match('/^[\p{Cyrillic}\s]+$/u', $data['full_name'])) {
        $issue = 'Полное ФИО содержит недопустимые символы (разрешены только русские буквы и пробелы).';
        $validationIssues[] = $issue;
        logger("WARNING", "Пользователь отправил некорректное полное ФИО: " . htmlspecialchars($data['full_name']));
    }

    // Валидация логина
    if (mb_strlen($data['userlogin'], 'UTF-8') > 20) {
        $issue = 'Логин превышает допустимую длину (максимум 20 символов).';
        $validationIssues[] = $issue;
        logger("WARNING", "Пользователь отправил слишком длинный логин: " . htmlspecialchars($data['userlogin']));
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['userlogin'])) {
        $issue = 'Логин содержит недопустимые символы (разрешены только латинские буквы, цифры и "_").';
        $validationIssues[] = $issue;
        logger("WARNING", "Пользователь отправил некорректный логин: " . htmlspecialchars($data['userlogin']));
    }

    // Валидация пароля
    if (mb_strlen($data['password'], 'UTF-8') < 10) {
        $issue = 'Пароль слишком короткий (минимум 10 символов).';
        $validationIssues[] = $issue;
        logger("WARNING", "Пользователь отправил слишком короткий пароль.");
    } elseif ($data['password'] === $data['userlogin']) {
        $issue = 'Пароль не должен совпадать с логином.';
        $validationIssues[] = $issue;
        logger("WARNING", "Пользователь установил пароль, совпадающий с логином.");
    }

    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $issue = 'Некорректный формат email.';
        $validationIssues[] = $issue;
        logger("WARNING", "Пользователь отправил некорректный email: " . htmlspecialchars($data['email']));
    }

    // Если есть ошибки валидации, завершаем выполнение
    if (!empty($validationIssues)) {
        $errorMessage = implode(' ', $validationIssues);
        logger("ERROR", "Ошибка валидации: " . $errorMessage);
        echo json_encode(['success' => false, 'message' => $errorMessage], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $pdo = connectToDatabase();

    // Генерация GUID для пользователя
    $userid = generateGUID();

    // Подготовка данных для записи в таблицу users
    $full_name = trim($data['full_name']);
    $userlogin = trim($data['userlogin']);
    $password_hash = password_hash(trim($data['password']), PASSWORD_DEFAULT);
    $email = trim($data['email']);

    // Проверка на существование пользователя с таким логином
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE userlogin = :userlogin");
    $stmt->execute(['userlogin' => $userlogin]);
    if ($stmt->fetchColumn() > 0) {
        logger("ERROR", "Пользователь с таким логином уже существует: " . htmlspecialchars($userlogin));
        echo json_encode(['success' => false, 'message' => 'Пользователь с таким логином уже существует.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Проверка на существование пользователя с таким email
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetchColumn() > 0) {
        logger("ERROR", "Пользователь с таким email уже существует: " . htmlspecialchars($email));
        echo json_encode(['success' => false, 'message' => 'Пользователь с таким email уже существует.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Получение текущего времени
    $currentTime = date('Y-m-d H:i:s'); // Формат: ГГГГ-ММ-ДД ЧЧ:ММ:СС

    $result = $stmt->execute([
        'userid' => $userid,
        'full_name' => $full_name,
        'userlogin' => $userlogin,
        'password_hash' => $password_hash,
        'email' => $email,
        'regtimes' => $currentTime // Добавляем время регистрации
    ]);

    if (!$result) {
        logger("ERROR", "Ошибка при создании пользователя в таблице users.");
        echo json_encode(['success' => false, 'message' => 'Ошибка при создании пользователя.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

// ВОТ ТУТ НУЖНО ВЫЗВАТЬ ВЫДАЧУ РОЛЕЙ ДЛЯ ПОЛЬЗОВАТЕЛЯ

    // Отправка успешного ответа клиенту
    logger("INFO", "Пользователь и его привилегии успешно созданы.");
    echo json_encode(['success' => true, 'message' => 'Пользователь успешно создан.'], JSON_UNESCAPED_UNICODE);
?>