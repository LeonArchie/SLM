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
        echo json_encode(['success' => false, 'message' => 'Ошибка сервера: файл function.php не найден.']);
        exit();
    }

    // Подключаем файл function.php
    require_once $file_path;

    // Запускаем сессию, если она ещё не запущена
    startSessionIfNotStarted();

    // Чтение входящих данных как JSON
    $data = file_get_contents('php://input');
    if (!$data) {
        // Если данные пустые, логируем ошибку и возвращаем сообщение об ошибке
        logger("ERROR", "Пустой запрос.");
        echo json_encode(['success' => false, 'message' => 'Пустой запрос.'], JSON_UNESCAPED_UNICODE);
        exit();
    }
        
    // Декодируем JSON данные
    $data = json_decode($data, true);
        
    // Проверяем корректность JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Если JSON некорректен, логируем ошибку и возвращаем сообщение об ошибке
        logger("ERROR", "Неверный формат JSON.");
        echo json_encode(['success' => false, 'message' => 'Неверный формат JSON.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Проверка CSRF-токена
    if (empty($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
        // Если токен не совпадает, логируем ошибку и возвращаем сообщение об ошибке
        logger("ERROR", "Ошибка безопасности: неверный CSRF-токен.");
        echo json_encode(['success' => false, 'message' => 'Ошибка безопасности: неверный CSRF-токен.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Валидация входных данных
    $validationIssues = validateInputData($data);
    if (!empty($validationIssues)) {
        // Если есть ошибки валидации, логируем их и возвращаем сообщение об ошибке
        $errorMessage = implode(' ', $validationIssues);
        logger("ERROR", "Ошибка валидации: " . $errorMessage);
        echo json_encode(['success' => false, 'message' => $errorMessage], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Подключение к базе данных
    $pdo = connectToDatabase();

    // Генерация GUID для пользователя
    $userid = generateGUID();

    // Подготовка данных для записи в таблицу users
    $full_name = trim($data['full_name']);
    $userlogin = trim($data['userlogin']);
    $password_hash = password_hash(trim($data['password']), PASSWORD_DEFAULT);
    $email = trim($data['email']);
    $currentTime = date('Y-m-d H:i:s'); // Текущее время

    // Начало транзакции
    $pdo->beginTransaction();
    try {
        // Проверка на существование пользователя с таким логином или email
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE userlogin = :userlogin OR email = :email");
        $stmt->execute(['userlogin' => $userlogin, 'email' => $email]);
        if ($stmt->fetchColumn() > 0) {
            // Если пользователь с таким логином или email уже существует, логируем ошибку и возвращаем сообщение
            logger("ERROR", "Пользователь с таким логином или email уже существует: " . htmlspecialchars($userlogin) . ", " . htmlspecialchars($email));
            echo json_encode(['success' => false, 'message' => 'Пользователь с таким логином или email уже существует.'], JSON_UNESCAPED_UNICODE);
            exit();
        }

        // Вставка данных в таблицу users
        $stmt = $pdo->prepare("INSERT INTO users (userid, full_name, userlogin, password_hash, email, regtimes) VALUES (:userid, :full_name, :userlogin, :password_hash, :email, :regtimes)");
        $stmt->execute([
            'userid' => $userid,
            'full_name' => $full_name,
            'userlogin' => $userlogin,
            'password_hash' => $password_hash,
            'email' => $email,
            'regtimes' => $currentTime
        ]);

        // Завершение транзакции
        $pdo->commit();

        // ВОТ ТУТ НУЖНО ВЫЗВАТЬ ВЫДАЧУ РОЛЕЙ ДЛЯ ПОЛЬЗОВАТЕЛЯ

        // Успешный ответ
        logger("INFO", "Пользователь успешно создан: " . json_encode([
            'userid' => $userid,
            'userlogin' => $userlogin,
            'email' => $email,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'timestamp' => $currentTime
        ]));
        echo json_encode(['success' => true, 'message' => 'Пользователь успешно создан.'], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        // Откат транзакции в случае ошибки
        $pdo->rollBack();
        logger("ERROR", "Ошибка при создании пользователя: " . json_encode([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'data' => $data
        ]));
        echo json_encode(['success' => false, 'message' => 'Ошибка при создании пользователя.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    /**
     * Валидация входных данных.
     *
     * @param array $data Входные данные.
     * @return array Массив с ошибками валидации.
     */
    function validateInputData($data) {
        $validationIssues = [];

        // Валидация полного ФИО
        if (mb_strlen($data['full_name'], 'UTF-8') > 50) {
            $validationIssues[] = 'Полное ФИО превышает допустимую длину (максимум 50 символов).';
            logger("WARNING", "Пользователь отправил слишком длинное полное ФИО: " . htmlspecialchars($data['full_name']));
        } elseif (!preg_match('/^[\p{Cyrillic}\s]+$/u', $data['full_name'])) {
            $validationIssues[] = 'Полное ФИО содержит недопустимые символы (разрешены только русские буквы и пробелы).';
            logger("WARNING", "Пользователь отправил некорректное полное ФИО: " . htmlspecialchars($data['full_name']));
        }

        // Валидация логина
        if (mb_strlen($data['userlogin'], 'UTF-8') > 20) {
            $validationIssues[] = 'Логин превышает допустимую длину (максимум 20 символов).';
            logger("WARNING", "Пользователь отправил слишком длинный логин: " . htmlspecialchars($data['userlogin']));
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['userlogin'])) {
            $validationIssues[] = 'Логин содержит недопустимые символы (разрешены только латинские буквы, цифры и "_").';
            logger("WARNING", "Пользователь отправил некорректный логин: " . htmlspecialchars($data['userlogin']));
        }

        // Валидация пароля
        if (mb_strlen($data['password'], 'UTF-8') < 10) {
            $validationIssues[] = 'Пароль слишком короткий (минимум 10 символов).';
            logger("WARNING", "Пользователь отправил слишком короткий пароль.");
        } elseif ($data['password'] === $data['userlogin']) {
            $validationIssues[] = 'Пароль не должен совпадать с логином.';
            logger("WARNING", "Пользователь установил пароль, совпадающий с логином.");
        }

        // Валидация email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $validationIssues[] = 'Некорректный формат email.';
            logger("WARNING", "Пользователь отправил некорректный email: " . htmlspecialchars($data['email']));
        }

        return $validationIssues;
    }
?>