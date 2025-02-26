<?php
    // Инициализация вызваемых функций
    $file_path = __DIR__ . '/../include/function.php';
    if (!file_exists($file_path)) {
        echo json_encode(['success' => false, 'message' => 'Ошибка сервера: файл function.php не найден.']);
        exit();
    }
    require_once $file_path;
    logger("INFO", "Начало выполнения скрипта authorization.php.");
    startSessionIfNotStarted();

    // Получение времени жизни сессии из конфигурации
    $config_path = CONFIG_PATH; // Путь к config.json определен в function.php
    if (!file_exists($config_path)) {
        logger("ERROR", "Файл конфигурации config.json не найден.");
        echo json_encode(['success' => false, 'message' => 'Ошибка сервера: файл конфигурации не найден.']);
        exit();
    }

    // Чтение и декодирование JSON-конфигурации
    $config_content = file_get_contents($config_path);
    $config_data = json_decode($config_content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        logger("ERROR", "Ошибка чтения конфигурации: " . json_last_error_msg());
        echo json_encode(['success' => false, 'message' => 'Ошибка сервера: неверный формат конфигурации.']);
        exit();
    }

    // Получение session_timeout
    $session_timeout = $config_data['web']['session_timeout'] ?? 3600; // Значение по умолчанию: 3600 секунд
    logger("DEBUG", "Получено время жизни сессии из конфигурации: $session_timeout секунд.");

    // Установка времени жизни сессии
    session_set_cookie_params($session_timeout);
    session_regenerate_id(true); // Пересоздание ID сессии для безопасности

    // Проверка CSRF-токена
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        logger("ERROR", "Ошибка безопасности: неверный CSRF-токен.");
        echo json_encode(['success' => false, 'message' => 'Ошибка безопасности: неверный CSRF-токен.']);
        exit();
    }

    // Подключение к базе данных через connectToDatabase()
    try {
        $pdo = connectToDatabase(); // Вызов функции для подключения к БД
        if (!$pdo instanceof PDO) {
            throw new Exception("Объект PDO не создан.");
        }
    } catch (Exception $e) {
        logger("ERROR", "Ошибка подключения к базе данных: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Ошибка сервера: база данных недоступна.']);
        exit();
    }

    // Проверка, была ли отправлена форма
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        logger("INFO", "Начало обработки формы авторизации. Метод запроса: POST.");

        // Получение данных из формы
        $login = trim($_POST['login'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // Проверка на пустые поля
        if (empty($login) || empty($password)) {
            logger("ERROR", "Логин и пароль обязательны для заполнения!");
            echo json_encode(['success' => false, 'message' => 'Логин и пароль обязательны для заполнения!']);
            exit();
        }

        try {
            logger("INFO", "Попытка авторизации пользователя: " . $login);

            // Поиск пользователя в базе данных
            $sql = "SELECT userid, userlogin, password_hash, roleid, usernames, active FROM users WHERE userlogin = :userlogin";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['userlogin' => $login]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Проверка статуса активности пользователя
            if ($user && !$user['active']) {
                logger("ERROR", "Пользователь заблокирован: " . $login);
                echo json_encode(['success' => false, 'message' => 'Пользователь заблокирован']);
                exit();
            }

            // Проверка пароля
            if ($user && password_verify($password, $user['password_hash'])) {
                logger("INFO", "Успешная авторизация пользователя: " . $login);

                // Генерация уникального ID сессии
                $session_id = session_id();
                logger("DEBUG", "Сгенерирован ID сессии: " . $session_id);

                // Сохранение данных пользователя в сессии
                $_SESSION['username'] = htmlspecialchars($user['usernames']);
                $_SESSION['userid'] = $user['userid'];
                $_SESSION['roleid'] = $user['roleid'];
                $_SESSION['session_id'] = $session_id;

                logger("DEBUG", "Данные пользователя сохранены в сессии. Username: " . $_SESSION['username'] . ", UserID: " . $_SESSION['userid'] . ", RoleID: " . $_SESSION['roleid'] . ", SessionID: " . $_SESSION['session_id']);

                // Установка времени жизни сессии
                setcookie("session_id", $session_id, time() + $session_timeout, "/");

                // Успешная авторизация
                echo json_encode(['success' => true, 'message' => 'Успешная авторизация.', 'redirect' => '/dashboard.php']);
                exit();
            } else {
                // Неудачная авторизация
                logger("ERROR", "Неверный логин или пароль для пользователя: " . $login);
                sleep(2); // Задержка для защиты от брутфорса
                echo json_encode(['success' => false, 'message' => 'Неверный логин или пароль!']);
                exit();
            }
        } catch (PDOException $e) {
            logger("ERROR", "Ошибка выполнения запроса: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Произошла ошибка. Пожалуйста, попробуйте позже.']);
            exit();
        }
    } else {
        // Если форма не была отправлена, возвращаем ошибку
        logger("INFO", "Попытка доступа к authorization.php без отправки формы. Метод запроса: " . $_SERVER["REQUEST_METHOD"]);
        echo json_encode(['success' => false, 'message' => 'Недопустимый метод запроса.']);
        exit();
    }
?>