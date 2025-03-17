<?php
    // Инициализация вызываемых функций
    // Проверка существования файла function.php
    $file_path = __DIR__ . '/../include/function.php';
    if (!file_exists($file_path)) {
        // Логирование ошибки и возврат JSON-ответа, если файл не найден
        logger("ERROR", "Файл function.php не найден.");
        http_response_code(500); 
        echo json_encode(['success' => false, 'message' => 'Ошибка 0001: Ошибка сервера.']);
        exit();
    }
    require_once $file_path;

    // Получение времени жизни сессии из конфигурации
    // Проверка существования файла конфигурации
    $config_path = CONFIG_PATH; 
    if (!file_exists($config_path)) {
        // Логирование ошибки и возврат JSON-ответа, если файл конфигурации не найден
        logger("ERROR", "Файл конфигурации config.json не найден.");
        http_response_code(500); 
        echo json_encode(['success' => false, 'message' => 'Ошибка 0002: Ошибка сервера.']);
        exit();
    }

    // Чтение и декодирование JSON-конфигурации
    $config_content = file_get_contents($config_path);
    $config_data = json_decode($config_content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Логирование ошибки и возврат JSON-ответа, если произошла ошибка декодирования JSON
        logger("ERROR", "Ошибка чтения конфигурации: " . json_last_error_msg());
        http_response_code(500); 
        echo json_encode(['success' => false, 'message' => 'Ошибка 0003: Ошибка сервера.']);
        exit();
    }

    // Получение session_timeout из конфигурации или установка значения по умолчанию
    $session_timeout = $config_data['web']['session_timeout'] ?? 3600; // По умолчанию: 3600 секунд
    session_set_cookie_params($session_timeout);
    startSessionIfNotStarted();
    
    session_regenerate_id(true); // Пересоздание сессии для повышения безопасности

    // Проверка CSRF-токена
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        // Логирование ошибки и возврат JSON-ответа, если CSRF-токен неверный
        logger("ERROR", "Ошибка безопасности: неверный CSRF-токен.");
        audit("ERROR", "Попытка авторизации польльзователя" . $_POST['login'] . "с неверным CSRF-токеном.");
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Ошибка 0004: Обновите страницу и повторите попытку.']);
        exit();
    }

    try {
        // Подключение к базе данных
        $pdo = connectToDatabase();
        if (!$pdo instanceof PDO) {
            throw new Exception("Объект PDO не создан.");
        }
    } catch (Exception $e) {
        // Логирование ошибки и возврат JSON-ответа, если подключение к базе данных не удалось
        logger("ERROR", "Ошибка подключения к базе данных: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка 0005: Ошибка сервера.']);
        exit();
    }

    // Проверка, была ли отправлена форма
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        // Получение данных из формы
        $login = trim($_POST['login'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // Проверка на пустые поля
        if (empty($login) || empty($password)) {
            // Логирование ошибки и возврат JSON-ответа, если логин или пароль не заполнены
            logger("ERROR", "Логин и пароль обязательны для заполнения!");
            http_response_code(418); 
            echo json_encode(['success' => false, 'message' => 'Ошибка 0006: Логин и пароль обязательны для заполнения!']);
            exit();
        }

        try {
            // Логирование попытки авторизации
            logger("INFO", "Попытка авторизации пользователя: " . $login);

            // Поиск пользователя в базе данных
            $sql = "SELECT userid, userlogin, password_hash, full_name, active FROM users WHERE userlogin = :userlogin";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['userlogin' => $login]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Проверка статуса активности пользователя
            if ($user && !$user['active']) {
                // Логирование ошибки и возврат JSON-ответа, если пользователь заблокирован
                logger("ERROR", "Пользователь заблокирован: " . $login);
                audit("ERROR", "Попытка авторизации заблокированного пользователя: " . $login);
                http_response_code(403); 
                echo json_encode(['success' => false, 'message' => 'Ошибка 0007: Пользователь заблокирован!']);
                exit();
            }

            // Проверка пароля
            if ($user && password_verify($password, $user['password_hash'])) {
                // Логирование успешной авторизации
                logger("INFO", "Успешная авторизация пользователя: " . $login);
                audit("INFO", "Успешная авторизация пользователя: " . $login);

                // Генерация ID сессии
                $session_id = session_id();

                // Сохранение данных пользователя в сессии
                $_SESSION['username'] = htmlspecialchars($user['full_name']);
                $_SESSION['userid'] = $user['userid'];
                $_SESSION['session_id'] = $session_id;

                // Установка cookie с ID сессии
                setcookie("session_id", $session_id, time() + $session_timeout, "/");
                http_response_code(200); 
                // Успешная авторизация
                echo json_encode(['success' => true, 'message' => 'Успешная авторизация.', 'redirect' => '/dashboard.php']);
                exit();
            } else {
                // Логирование ошибки и возврат JSON-ответа, если логин или пароль неверные
                logger("ERROR", "Неверный логин или пароль для пользователя: " . $login);
                audit("ERROR", "Неудачная попытка авторизации пользователя: " . $login);
                sleep(5); // Задержка для защиты от брутфорса
                http_response_code(403); 
                echo json_encode(['success' => false, 'message' => 'Ошибка 0008: Неверный логин или пароль!']);
                exit();
            }
        } catch (PDOException $e) {
            // Логирование ошибки и возврат JSON-ответа, если произошла ошибка выполнения запроса
            logger("ERROR", "Ошибка выполнения запроса: " . $e->getMessage());
            http_response_code(500); 
            echo json_encode(['success' => false, 'message' => 'Ошибка 0009: Ошибка сервера.']);
            exit();
        }
    } else {
        // Логирование предупреждения и возврат JSON-ответа, если форма не была отправлена
        logger("WARNING", "Попытка доступа к authorization.php без отправки формы. Метод запроса: " . $_SERVER["REQUEST_METHOD"]);
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Ошибка 0010: Доступ запрещен.']);
        exit();
    }
?>