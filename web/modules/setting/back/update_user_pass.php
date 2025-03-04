<?php
    define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
    $file_path = ROOT_PATH . '/include/function.php';

    // Проверяем существование файла function.php
    if (!file_exists($file_path)) {
        echo json_encode(['success' => false, 'message' => 'Ошибка сервера: файл function.php не найден.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    require_once $file_path;

    // Логирование начала выполнения скрипта
    //logger("INFO", "Начало выполнения скрипта update_user_pass.php.");
    startSessionIfNotStarted();

    // Чтение входящих данных как JSON
    $rawData = file_get_contents('php://input');
    if (!$rawData) {
        logger("ERROR", "Пустой запрос.");
        echo json_encode(['success' => false, 'message' => 'Пустой запрос.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $data = json_decode($rawData, true);

    // Проверка корректности JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        logger("ERROR", "Неверный формат JSON.");
        echo json_encode(['success' => false, 'message' => 'Неверный формат JSON.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Проверка наличия обязательных полей
    if (empty($data['csrf_token']) || empty($data['admin_userid']) || empty($data['userid']) || empty($data['current_password']) || empty($data['new_password'])) {
        logger("ERROR", "Отсутствуют обязательные данные.");
        echo json_encode(['success' => false, 'message' => 'Необходимо заполнить все поля.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Проверка CSRF-токена
    logger("INFO", "Начало проверки CSRF-токена.");
    if ($data['csrf_token'] !== $_SESSION['csrf_token']) {
        logger("ERROR", "CSRF-токен не совпадает с ожидаемым значением.");
        echo json_encode(['success' => false, 'message' => 'Ошибка безопасности: неверный CSRF-токен.'], JSON_UNESCAPED_UNICODE);
        exit();
    }
    //logger("INFO", "CSRF-токен успешно проверен.");

    // Получение данных из JSON
    $admin_userid = trim($data['admin_userid']);
    $userid = trim($data['userid']);
    $current_password = trim($data['current_password']);
    $new_password = trim($data['new_password']);

    // Подключение к базе данных
    //logger("INFO", "Подключение к базе данных...");
    $pdo = connectToDatabase();
    //logger("INFO", "Успешно подключено к базе данных.");

    // Получение хеша пароля администратора из базы данных
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE userid = :admin_userid");
    $stmt->execute(['admin_userid' => $admin_userid]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        logger("ERROR", "Администратор не найден.");
        echo json_encode(['success' => false, 'message' => 'Администратор не найден.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Проверка пароля администратора
    if (!password_verify($current_password, $admin['password_hash'])) {
        logger("ERROR", "Неверный пароль администратора.");
        echo json_encode(['success' => false, 'message' => 'Неверный пароль администратора.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Хеширование нового пароля пользователя
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

    // Обновление пароля пользователя в базе данных
    $stmt = $pdo->prepare("UPDATE users SET password_hash = :password_hash WHERE userid = :userid");
    $result = $stmt->execute(['password_hash' => $new_password_hash, 'userid' => $userid]);

    if ($result) {
        logger("INFO", "Пароль пользователя успешно обновлен.");
        echo json_encode(['success' => true, 'message' => 'Пароль пользователя успешно изменен.'], JSON_UNESCAPED_UNICODE);
    } else {
        logger("ERROR", "Ошибка при обновлении пароля пользователя.");
        echo json_encode(['success' => false, 'message' => 'Ошибка при изменении пароля пользователя.'], JSON_UNESCAPED_UNICODE);
    }
?>