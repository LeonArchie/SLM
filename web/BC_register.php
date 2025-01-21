<?php
require_once 'include/function.php';

// Логирование начала выполнения скрипта
logger("INFO", "Начало выполнения скрипта BC_register.php.");

// Запуск сессии
startSessionIfNotStarted();
logger("INFO", "Сессия успешно запущена. ID сессии: " . session_id());

// Проверка авторизации
checkAuth();
logger("INFO", "Пользователь авторизован. Username: " . $_SESSION['username']);

// Генерация CSRF-токена
csrf_token();
logger("INFO", "CSRF-токен проверен.");

// Подключение к базе данных
require_once 'db_connect.php';
logger("INFO", "Подключение к базе данных успешно.");

// Получение данных из формы и удаление лишних пробелов
$email = trim($_POST['email']);
$username = trim($_POST['username']);
$password = trim($_POST['password']);
$roleid = trim($_POST['role']);
$usernames = trim($_POST['usernames']);

logger("DEBUG", "Данные из формы:");
logger("DEBUG", "Email: " . $email);
logger("DEBUG", "Username: " . $username);
logger("DEBUG", "Role ID: " . $roleid);
logger("DEBUG", "Usernames: " . $usernames);

// Массив для хранения ошибок валидации
$errors = [];

// Валидация поля "E-mail"
if (empty($email)) {
    $errors['email'] = 'Поле "E-mail" обязательно для заполнения.';
    logger("ERROR", "Ошибка валидации: Поле 'E-mail' обязательно для заполнения.");
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Некорректный формат E-mail.';
    logger("ERROR", "Ошибка валидации: Некорректный формат E-mail.");
}

// Валидация поля "Логин"
if (empty($username)) {
    $errors['username'] = 'Поле "Логин" обязательно для заполнения.';
    logger("ERROR", "Ошибка валидации: Поле 'Логин' обязательно для заполнения.");
} elseif (strlen($username) < 3) {
    $errors['username'] = 'Логин должен содержать не менее 3 символов.';
    logger("ERROR", "Ошибка валидации: Логин должен содержать не менее 3 символов.");
}

// Валидация поля "Пароль"
if (empty($password)) {
    $errors['password'] = 'Поле "Пароль" обязательно для заполнения.';
    logger("ERROR", "Ошибка валидации: Поле 'Пароль' обязательно для заполнения.");
} elseif (strlen($password) < 6) {
    $errors['password'] = 'Пароль должен содержать не менее 6 символов.';
    logger("ERROR", "Ошибка валидации: Пароль должен содержать не менее 6 символов.");
}

// Валидация поля "Роль"
if (empty($roleid)) {
    $errors['role'] = 'Поле "Роль" обязательно для заполнения.';
    logger("ERROR", "Ошибка валидации: Поле 'Роль' обязательно для заполнения.");
}

// Валидация поля "Имя пользователя"
if (empty($usernames)) {
    $errors['usernames'] = 'Поле "Имя пользователя" обязательно для заполнения.';
    logger("ERROR", "Ошибка валидации: Поле 'Имя пользователя' обязательно для заполнения.");
} elseif (strlen($usernames) < 2) {
    $errors['usernames'] = 'Имя пользователя должно содержать не менее 2 символов.';
    logger("ERROR", "Ошибка валидации: Имя пользователя должно содержать не менее 2 символов.");
}

// Если есть ошибки валидации, возвращаем их
if (!empty($errors)) {
    logger("ERROR", "Обнаружены ошибки валидации: " . print_r($errors, true));
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Ошибка валидации данных.',
        'errors' => $errors
    ]);
    exit();
}

// Проверка существования пользователя с таким же логином или email
$sqlCheck = "SELECT userid FROM users WHERE userlogin = :userlogin OR email = :email";
$stmtCheck = $pdo->prepare($sqlCheck);
$stmtCheck->execute([':userlogin' => $username, ':email' => $email]);

// Если пользователь с таким логином или email уже существует
if ($stmtCheck->rowCount() > 0) {
    logger("ERROR", "Пользователь с таким логином или email уже существует.");
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Пользователь с таким логином или email уже существует.'
    ]);
    exit();
}

try {
    // Генерация GUID с использованием функции generateGUID() из function.php
    $userid = generateGUID();
    logger("INFO", "Сгенерирован GUID: " . $userid);

    // Хэширование пароля для безопасного хранения в базе данных
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    logger("INFO", "Пароль успешно хэширован.");

    // Получение текущего времени для записи времени регистрации
    $regtimes = date('Y-m-d H:i:s');
    logger("INFO", "Время регистрации: " . $regtimes);

    // Подготовка SQL-запроса для вставки данных в таблицу users
    $sql = "INSERT INTO users (userid, userlogin, password_hash, email, roleid, regtimes, usernames) 
            VALUES (:userid, :userlogin, :password_hash, :email, :roleid, :regtimes, :usernames)";
    $stmt = $pdo->prepare($sql);
    logger("INFO", "Подготовлен SQL-запрос для вставки данных.");

    // Выполнение запроса с привязкой параметров
    $stmt->execute([
        ':userid' => $userid,
        ':userlogin' => $username,
        ':password_hash' => $password_hash,
        ':email' => $email,
        ':roleid' => $roleid,
        ':regtimes' => $regtimes,
        ':usernames' => $usernames
    ]);
    logger("INFO", "Данные успешно вставлены в таблицу users.");

    // Установка заголовка для возврата JSON
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => 'Регистрация прошла успешно!'
    ]);
    logger("INFO", "Регистрация прошла успешно. Ответ отправлен клиенту.");
    exit();
} catch (PDOException $e) {
    logger("ERROR", "Ошибка при регистрации пользователя: " . $e->getMessage());
    logger("DEBUG", "SQL: " . $sql);
    logger("DEBUG", "Параметры: " . print_r([
        ':userid' => $userid,
        ':userlogin' => $username,
        ':password_hash' => $password_hash,
        ':email' => $email,
        ':roleid' => $roleid,
        ':regtimes' => $regtimes,
        ':usernames' => $usernames
    ], true));

    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Произошла ошибка при регистрации. Пожалуйста, попробуйте позже.'
    ]);
    logger("ERROR", "Ошибка отправлена клиенту.");
    exit();
}
?>