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
        echo json_encode(['success' => false, 'message' => 'Ошибка 0038: Ошибка сервера.']);
        exit();
    }

    // Подключаем файл function.php.
    require_once $file_path;

    // Запускаем сессию, если она ещё не была запущена.
    startSessionIfNotStarted();

    // Проверяем, был ли запрос отправлен методом POST.
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Логируем информацию о получении POST-запроса для обновления данных профиля пользователя.
        logger("INFO", "Получен POST-запрос для обновления данных профиля пользователя.");
        
        // Декодируем JSON-данные, полученные из тела запроса.
        $data = json_decode(file_get_contents('php://input'), true);

        // Проверяем, произошла ли ошибка при декодировании JSON.
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Логируем ошибку и выводим сообщение об ошибке в формате JSON.
            logger("ERROR", "Ошибка при декодировании JSON: " . json_last_error_msg());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Ошибка 0039: Ошибка сервера.']);
            exit();
        }

        logger("INFO", "Получен запрос для обновления данных профиля пользователя. Полученные данные: $data");
        audit("INFO", "Получен запрос для обновления данных профиля пользователя. Полученные данные: $data");
        
        // Проверяем, передан ли CSRF-токен в данных.
        if (empty($data['csrf_token'])) {
            // Логируем ошибку и выводим сообщение об ошибке безопасности.
            logger("ERROR", "CSRF-токен не передан в данных.");
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Ошибка 0040: Обновите страницу и повторите попытку.']);
            exit();
        }

        // Проверяем, совпадает ли CSRF-токен из запроса с токеном, хранящимся в сессии.
        if ($data['csrf_token'] !== $_SESSION['csrf_token']) {
            // Логируем ошибку и выводим сообщение об ошибке безопасности.
            logger("ERROR", "CSRF-токен не совпадает с ожидаемым значением.");
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Ошибка 0041: Обновите страницу и повторите попытку.']);
            exit();
        }

        // Массив для хранения ошибок валидации.
        $validationIssues = [];

        // Валидация фамилии.
        if (isset($data['lastName']) && $data['lastName'] !== "") {
            if (mb_strlen($data['lastName'], 'UTF-8') > 20) {
                $issue = 'Фамилия превышает допустимую длину (максимум 20 символов).';
                $validationIssues[] = $issue;
                logger("WARNING", "Пользователь отправил слишком длинную фамилию: " . htmlspecialchars($data['lastName']));
                echo json_encode(['success' => false, 'message' => $issue]);
                exit();
            } elseif (!preg_match('/^\p{Cyrillic}+$/u', $data['lastName'])) {
                $issue = 'Фамилия содержит недопустимые символы (разрешены только русские буквы).';
                $validationIssues[] = $issue;
                logger("WARNING", "Пользователь отправил некорректную фамилию: " . htmlspecialchars($data['lastName']));
                echo json_encode(['success' => false, 'message' => $issue]);
                exit();
            }
        }

        // Валидация имени.
        if (isset($data['firstName']) && $data['firstName'] !== "") {
            if (mb_strlen($data['firstName'], 'UTF-8') > 20) {
                $issue = 'Имя превышает допустимую длину (максимум 20 символов).';
                $validationIssues[] = $issue;
                logger("WARNING", "Пользователь отправил слишком длинное имя: " . htmlspecialchars($data['firstName']));
                echo json_encode(['success' => false, 'message' => $issue]);
                exit();
            } elseif (!preg_match('/^\p{Cyrillic}+$/u', $data['firstName'])) {
                $issue = 'Имя содержит недопустимые символы (разрешены только русские буквы).';
                $validationIssues[] = $issue;
                logger("WARNING", "Пользователь отправил некорректное имя: " . htmlspecialchars($data['firstName']));
                echo json_encode(['success' => false, 'message' => $issue]);
                exit();
            }
        }

        // Валидация полного ФИО.
        if (isset($data['fullName']) && $data['fullName'] !== "") {
            if (mb_strlen($data['fullName'], 'UTF-8') > 50) {
                $issue = 'Полное ФИО превышает допустимую длину (максимум 50 символов).';
                $validationIssues[] = $issue;
                logger("WARNING", "Пользователь отправил слишком длинное полное ФИО: " . htmlspecialchars($data['fullName']));
                echo json_encode(['success' => false, 'message' => $issue]);
                exit();
            } elseif (!preg_match('/^[\p{Cyrillic}\s]+$/u', $data['fullName'])) {
                $issue = 'Полное ФИО содержит недопустимые символы (разрешены только русские буквы и пробелы).';
                $validationIssues[] = $issue;
                logger("WARNING", "Пользователь отправил некорректное полное ФИО: " . htmlspecialchars($data['fullName']));
                echo json_encode(['success' => false, 'message' => $issue]);
                exit();
            }
        }

        // Валидация email.
        if (isset($data['email']) && $data['email'] !== "") {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $issue = 'Некорректный формат email.';
                $validationIssues[] = $issue;
                logger("WARNING", "Пользователь отправил некорректный email: " . htmlspecialchars($data['email']));
                echo json_encode(['success' => false, 'message' => $issue]);
                exit();
            }
        }

        // Валидация телефона.
        if (isset($data['phone']) && $data['phone'] !== "") {
            if (!preg_match('/^\+7\d{10}$/', $data['phone'])) {
                $issue = 'Некорректный формат телефона (ожидается +7XXXXXXXXXX).';
                $validationIssues[] = $issue;
                logger("WARNING", "Пользователь отправил некорректный телефон: " . htmlspecialchars($data['phone']));
                echo json_encode(['success' => false, 'message' => $issue]);
                exit();
            }
        }

        // Валидация Telegram Username.
        if (isset($data['telegramUsername']) && $data['telegramUsername'] !== "") {
            if (mb_strlen($data['telegramUsername'], 'UTF-8') > 20) {
                $issue = 'Telegram Username превышает допустимую длину (максимум 20 символов).';
                $validationIssues[] = $issue;
                logger("WARNING", "Пользователь отправил слишком длинный Telegram Username: " . htmlspecialchars($data['telegramUsername']));
                echo json_encode(['success' => false, 'message' => $issue]);
                exit();
            } elseif (!preg_match('/^\p{Cyrillic}+$/u', $data['telegramUsername'])) {
                $issue = 'Telegram Username содержит недопустимые символы (должен начинаться с @ и содержать только буквы, цифры и _).';
                $validationIssues[] = $issue;
                logger("WARNING", "Пользователь отправил некорректный Telegram Username: " . htmlspecialchars($data['telegramUsername']));
                echo json_encode(['success' => false, 'message' => $issue]);
                exit();
            }
        }

        // Валидация Telegram ID.
        if (isset($data['telegramID']) && $data['telegramID'] !== "") {
            if (!ctype_digit($data['telegramID'])) {
                $issue = 'Telegram ID должен содержать только цифры.';
                $validationIssues[] = $issue;
                logger("WARNING", "Пользователь отправил некорректный Telegram ID: " . htmlspecialchars($data['telegramID']));
                echo json_encode(['success' => false, 'message' => $issue]);
                exit();
            } elseif (strlen($data['telegramID']) > 15) {
                $issue = 'Telegram ID превышает допустимую длину (максимум 15 цифр).';
                $validationIssues[] = $issue;
                logger("WARNING", "Пользователь отправил слишком длинный Telegram ID: " . htmlspecialchars($data['telegramID']));
                echo json_encode(['success' => false, 'message' => $issue]);
                exit();
            }
        }

        // Если есть ошибки валидации, логируем их и выводим сообщение пользователю.
        if (!empty($validationIssues)) {
            foreach ($validationIssues as $issue) {
                logger("WARNING", "Ошибка валидации: $issue");
            }
            logger("ERROR", "Валидация завершилась с ошибками. Отправка сообщения пользователю.");
            audit("INFO", "Валидация завершилась с ошибками. $issue");
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => implode('. ', $validationIssues)]);
            exit();
        }

        // Пытаемся обновить данные пользователя в базе данных.
        try {
            // Подключаемся к базе данных.
            $pdo = connectToDatabase();

            // Подготавливаем SQL-запрос для обновления данных пользователя.
            $stmt = $pdo->prepare("UPDATE users SET 
                userlogin = :login,
                family = :family, 
                name = :name, 
                full_name = :full_name, 
                tg_username = :tg_username, 
                tg_id = :tg_id, 
                email = :email, 
                telephone = :telephone,
                api_key = :apiKey
                WHERE userid = :userid");

            // Выполняем запрос с передачей данных из запроса.
            $stmt->execute([
                ':login' => $data['login'] ?? null,
                ':family' => $data['lastName'] ?? null,
                ':name' => $data['firstName'] ?? null,
                ':full_name' => $data['fullName'] ?? null,
                ':tg_username' => $data['telegramUsername'] ?? null,
                ':tg_id' => $data['telegramID'] ?? null,
                ':email' => $data['email'] ?? null,
                ':telephone' => $data['phone'] ?? null,
                ':apiKey' => $data['apiKey'] ?? null,
                ':userid' =>  $data['userID'] ?? null
            ]);

            // Проверяем, были ли обновлены данные.
            if ($stmt->rowCount() > 0) {
                // Логируем успешное обновление данных.
                logger("INFO", "Данные пользователя с UserID=" . htmlspecialchars($data['userID'] ?? 'не указано') . " успешно обновлены.");
                audit("INFO", "Данные пользователя с UserID=" . htmlspecialchars($data['userID'] ?? 'не указано') . " успешно обновлены.");
            } else {
                // Логируем предупреждение, если данные не были обновлены.
                logger("WARNING", "Обновление данных не затронуло ни одной записи. Возможно, UserID не найден.");
                audit("WARNING", "Обновление данных не затронуло ни одной записи. Возможно, UserID не найден.");
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Ошибка 0042: Данные не были обновлены.']);
                exit();
            }

            // Выводим сообщение об успешном обновлении данных.
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Ошибка 0043: Данные пользователя обновлены.']);
        } catch (PDOException $e) {
            // Логируем ошибку, если произошла ошибка при работе с базой данных.
            logger("ERROR", "Произошла ошибка при работе с базой данных: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Ошибка 0044: Ошибка сервера.']);
            exit();
        }
    }
?>