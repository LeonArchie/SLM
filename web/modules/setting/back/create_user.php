<?php
define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
$file_path = ROOT_PATH . '/include/function.php';

// Проверяем существование файла function.php
if (!file_exists($file_path)) {
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера: файл function.php не найден.']);
    exit();
}

require_once $file_path;

// Логирование начала выполнения скрипта
logger("INFO", "Начало выполнения скрипта create-user.php.");

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
} else {
    logger("INFO", "Полное ФИО успешно прошло валидацию: " . htmlspecialchars($data['full_name']));
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
} else {
    logger("INFO", "Логин успешно прошел валидацию: " . htmlspecialchars($data['userlogin']));
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
} else {
    logger("INFO", "Пароль успешно прошел валидацию.");
}

// Валидация email
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $issue = 'Некорректный формат email.';
    $validationIssues[] = $issue;
    logger("WARNING", "Пользователь отправил некорректный email: " . htmlspecialchars($data['email']));
} else {
    logger("INFO", "Email успешно прошел валидацию: " . htmlspecialchars($data['email']));
}

// Если есть ошибки валидации, завершаем выполнение
if (!empty($validationIssues)) {
    $errorMessage = implode(' ', $validationIssues);
    logger("ERROR", "Ошибка валидации: " . $errorMessage);
    echo json_encode(['success' => false, 'message' => $errorMessage], JSON_UNESCAPED_UNICODE);
    exit();
}

// Подключение к базе данных
$pdo = connectToDatabase();
logger("DEBUG", "Успешное подключение к базе данных.");

// Генерация GUID для пользователя
$userid = generateGUID();

// Подготовка данных для записи в таблицу users
$full_name = trim($data['full_name']);
$userlogin = trim($data['userlogin']);
$password_hash = password_hash(trim($data['password']), PASSWORD_DEFAULT);
$email = trim($data['email']);

// Логирование значений перед обработкой
logger("DEBUG", "Получены данные для создания пользователя: full_name=" . htmlspecialchars($full_name) . 
       ", userlogin=" . htmlspecialchars($userlogin) . 
       ", email=" . htmlspecialchars($email));

// Проверка на существование пользователя с таким логином
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE userlogin = :userlogin");
$stmt->execute(['userlogin' => $userlogin]);
if ($stmt->fetchColumn() > 0) {
    logger("ERROR", "Пользователь с таким логином уже существует: " . htmlspecialchars($userlogin));
    echo json_encode(['success' => false, 'message' => 'Пользователь с таким логином уже существует.'], JSON_UNESCAPED_UNICODE);
    exit();
}

logger("INFO", "Логин проверрен успешно.");

// Проверка на существование пользователя с таким email
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
$stmt->execute(['email' => $email]);
if ($stmt->fetchColumn() > 0) {
    logger("ERROR", "Пользователь с таким email уже существует: " . htmlspecialchars($email));
    echo json_encode(['success' => false, 'message' => 'Пользователь с таким email уже существует.'], JSON_UNESCAPED_UNICODE);
    exit();
}

logger("INFO", "e-mail проверрен успешно.");

// Подключение к базе данных
$pdo = connectToDatabase();
logger("DEBUG", "Успешное подключение к базе данных.");

// Генерация GUID для пользователя
$userid = generateGUID();

// Подготовка данных для записи в таблицу users
$full_name = trim($data['full_name']);
$userlogin = trim($data['userlogin']);
$password_hash = password_hash(trim($data['password']), PASSWORD_DEFAULT);
$email = trim($data['email']);
$roleName = trim($data['role'] ?? ''); // Получаем название роли из входных данных

// Логирование значений перед обработкой
logger("DEBUG", "Получены данные для создания пользователя: full_name=" . htmlspecialchars($full_name) . 
       ", userlogin=" . htmlspecialchars($userlogin) . 
       ", email=" . htmlspecialchars($email) . 
       ", role=" . htmlspecialchars($roleName));

// Проверка наличия роли
if (empty($roleName)) {
    logger("ERROR", "Роль не указана.");
    echo json_encode(['success' => false, 'message' => 'Необходимо выбрать роль.'], JSON_UNESCAPED_UNICODE);
    exit();
}

// Поиск roleid по названию роли в таблице name_rol
$stmt = $pdo->prepare("SELECT roleid FROM name_rol WHERE names_rol = :roleName");
$stmt->execute(['roleName' => $roleName]);
$roleid = $stmt->fetchColumn();

if (!$roleid) {
    logger("ERROR", "Роль с названием '{$roleName}' не найдена в базе данных.");
    echo json_encode(['success' => false, 'message' => "Роль '{$roleName}' не найдена."], JSON_UNESCAPED_UNICODE);
    exit();
}

logger("INFO", "Найден roleid для роли: {$roleName} (ID: {$roleid})");

// Проверка на существование пользователя с таким логином
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE userlogin = :userlogin");
$stmt->execute(['userlogin' => $userlogin]);
if ($stmt->fetchColumn() > 0) {
    logger("ERROR", "Пользователь с таким логином уже существует: " . htmlspecialchars($userlogin));
    echo json_encode(['success' => false, 'message' => 'Пользователь с таким логином уже существует.'], JSON_UNESCAPED_UNICODE);
    exit();
}

logger("INFO", "Логин проверен успешно.");

// Проверка на существование пользователя с таким email
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
$stmt->execute(['email' => $email]);
if ($stmt->fetchColumn() > 0) {
    logger("ERROR", "Пользователь с таким email уже существует: " . htmlspecialchars($email));
    echo json_encode(['success' => false, 'message' => 'Пользователь с таким email уже существует.'], JSON_UNESCAPED_UNICODE);
    exit();
}

logger("INFO", "E-mail проверен успешно.");

// Запись данных в таблицу users вместе с roleid и regtimes
$stmt = $pdo->prepare("INSERT INTO users (userid, full_name, userlogin, password_hash, email, active, add_ldap, roleid, regtimes) 
                       VALUES (:userid, :full_name, :userlogin, :password_hash, :email, TRUE, FALSE, :roleid, :regtimes)");

// Получение текущего времени
$currentTime = date('Y-m-d H:i:s'); // Формат: ГГГГ-ММ-ДД ЧЧ:ММ:СС

$result = $stmt->execute([
    'userid' => $userid,
    'full_name' => $full_name,
    'userlogin' => $userlogin,
    'password_hash' => $password_hash,
    'email' => $email,
    'roleid' => $roleid, // Добавляем roleid
    'regtimes' => $currentTime // Добавляем время регистрации
]);

if (!$result) {
    logger("ERROR", "Ошибка при создании пользователя в таблице users.");
    echo json_encode(['success' => false, 'message' => 'Ошибка при создании пользователя.'], JSON_UNESCAPED_UNICODE);
    exit();
}

logger("INFO", "Пользователь успешно создан в таблице users с ролью: {$roleName} (ID: {$roleid}) и временем регистрации: {$currentTime}.");

// Чтение конфигурационного файла для получения ролей по умолчанию
$configPath = ROOT_PATH . '/config/config.json';
if (!file_exists($configPath)) {
    logger("ERROR", "Файл конфигурации не найден: $configPath");
    echo json_encode(['success' => false, 'message' => 'Файл конфигурации не найден.'], JSON_UNESCAPED_UNICODE);
    exit();
}

$configData = json_decode(file_get_contents($configPath), true);
if (empty($configData['roles']['default'])) {
    logger("ERROR", "Отсутствует массив default в секции roles в файле конфигурации.");
    echo json_encode(['success' => false, 'message' => 'Ошибка в конфигурационном файле.'], JSON_UNESCAPED_UNICODE);
    exit();
}

$defaultRoles = $configData['roles']['default'];

// Добавление записей в таблицу privileges
foreach ($defaultRoles as $module_id) {
    $privilegeId = generateGUID(); // Генерация уникального ID для каждой записи
    $stmt = $pdo->prepare("INSERT INTO privileges (id, userid, module_id) 
                           VALUES (:id, :userid, :module_id)");
    $privilegeResult = $stmt->execute([
        'id' => $privilegeId,
        'userid' => $userid,
        'module_id' => $module_id
    ]);

    if (!$privilegeResult) {
        logger("WARNING", "Не удалось создать запись в таблице privileges для module_id: $module_id.");
    } else {
        logger("INFO", "Запись успешно создана в таблице privileges для module_id: $module_id.");
    }
}

// Отправка успешного ответа клиенту
logger("INFO", "Пользователь и его привилегии успешно созданы.");
echo json_encode(['success' => true, 'message' => 'Пользователь успешно создан.'], JSON_UNESCAPED_UNICODE);
?>