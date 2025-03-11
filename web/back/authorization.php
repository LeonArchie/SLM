<?php
    // Инициализация вызваемых функций
    $file_path = __DIR__ . '/../include/function.php';
    if (!file_exists($file_path)) {
        echo json_encode(['success' => false, 'message' => 'Ошибка 0001: файл function.php не найден.']);
        exit();
    }
    require_once $file_path;

    // Получение времени жизни сессии из конфигурации
    $config_path = CONFIG_PATH; 
    if (!file_exists($config_path)) {
        logger("ERROR", "Файл конфигурации config.json не найден.");
        echo json_encode(['success' => false, 'message' => 'Ошибка 0002: Обратитесь к администратору.']);
        exit();
    }

    // Чтение и декодирование JSON-конфигурации
    $config_content = file_get_contents($config_path);
    $config_data = json_decode($config_content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        logger("ERROR", "Ошибка чтения конфигурации: " . json_last_error_msg());
        echo json_encode(['success' => false, 'message' => 'Ошибка 0003: Обратитесь к администратору.']);
        exit();
    }

    // Получение session_timeout
    $session_timeout = $config_data['web']['session_timeout'] ?? 3600; // По умолчанию: 3600 секунд
    session_set_cookie_params($session_timeout);
    startSessionIfNotStarted();
    
    session_regenerate_id(true); // Пересоздание сессии

    // Проверка токена
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        logger("ERROR", "Ошибка безопасности: неверный CSRF-токен.");
        echo json_encode(['success' => false, 'message' => 'Ошибка 0004: неверный CSRF-токен.']);
        exit();
    }

    try {
        $pdo = connectToDatabase();
        if (!$pdo instanceof PDO) {
            throw new Exception("Объект PDO не создан.");
        }
    } catch (Exception $e) {
        logger("ERROR", "Ошибка подключения к базе данных: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Ошибка 0005: Обратитесь к администратору.']);
        exit();
    }

    // Проверка, была ли отправлена форма
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        // Получение данных из формы
        $login = trim($_POST['login'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // Проверка на пустые поля
        if (empty($login) || empty($password)) {
            logger("ERROR", "Логин и пароль обязательны для заполнения!");
            echo json_encode(['success' => false, 'message' => 'Ошибка 0006: Логин и пароль обязательны для заполнения!']);
            exit();
        }

        try {
            logger("INFO", "Попытка авторизации пользователя: " . $login);

            // Поиск пользователя в базе данных
            $sql = "SELECT userid, userlogin, password_hash, full_name, active FROM users WHERE userlogin = :userlogin";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['userlogin' => $login]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Проверка статуса активности пользователя
            if ($user && !$user['active']) {
                logger("ERROR", "Пользователь заблокирован: " . $login);
                echo json_encode(['success' => false, 'message' => 'Ошибка 0007: Пользователь заблокирован']);
                exit();
            }

            // Проверка пароля
            if ($user && password_verify($password, $user['password_hash'])) {
                logger("INFO", "Успешная авторизация пользователя: " . $login);

                // Генерация ID сессии
                $session_id = session_id();

                // Сохранение данных пользователя в сессии
                $_SESSION['username'] = htmlspecialchars($user['full_name']);
                $_SESSION['userid'] = $user['userid'];
                $_SESSION['session_id'] = $session_id;

                setcookie("session_id", $session_id, time() + $session_timeout, "/");

                // Успешная авторизация
                echo json_encode(['success' => true, 'message' => 'Успешная авторизация.', 'redirect' => '/dashboard.php']);
                exit();
            } else {
                // Неудачная авторизация
                logger("ERROR", "Неверный логин или пароль для пользователя: " . $login);
                sleep(5); // Задержка для защиты от брутфорса
                echo json_encode(['success' => false, 'message' => 'Ошибка 0008: Неверный логин или пароль!']);
                exit();
            }
        } catch (PDOException $e) {
            logger("ERROR", "Ошибка выполнения запроса: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Ошибка 0009: Пожалуйста, попробуйте позже.']);
            exit();
        }
    } else {
        // Если форма не была отправлена, возвращаем ошибку
        logger("WARNING", "Попытка доступа к authorization.php без отправки формы. Метод запроса: " . $_SERVER["REQUEST_METHOD"]);
        echo json_encode(['success' => false, 'message' => 'Ошибка 0010: Недопустимый метод запроса.']);
        exit();
    }
?>