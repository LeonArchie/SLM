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
    logger("INFO", "Начало выполнения скрипта save_user_data.php.");

        startSessionIfNotStarted();


        НЕТ ПРОВЕРКИ ТОКЕНА НЕТ ПРОВЕРКИ ТОКЕНА НЕТ ПРОВЕРКИ ТОКЕНА НЕТ ПРОВЕРКИ ТОКЕНА НЕТ ПРОВЕРКИ ТОКЕНА НЕТ ПРОВЕРКИ ТОКЕНА НЕТ ПРОВЕРКИ ТОКЕНА 

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Логирование начала обработки запроса
        logger("INFO", "Получен POST-запрос для обновления данных собственного профиля пользователя.");
        //logger("DEBUG", "Сырые данные из php://input: " . file_get_contents('php://input'));
        
        $data = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            logger("ERROR", "Ошибка при декодировании JSON: " . json_last_error_msg());
            echo json_encode(['success' => false, 'message' => 'Неверный формат данных.']);
            exit();
        }

    // Логирование полученных данных после декодирования
    //logger("DEBUG", "Декодированные данные: " . json_encode($data));
        // Валидация данных
        //logger("INFO", "Начало валидации данных.");
        $validationIssues = [];

        // Проверка фамилии (пустое значение допустимо)
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
            } else {
                //logger("INFO", "Фамилия успешно прошла валидацию: " . htmlspecialchars($data['lastName']));
            }
        } else {
            //logger("INFO", "Фамилия не была передана или пустая. Пропуск валидации.");
        }

        // Проверка имени (пустое значение допустимо)
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
            } else {
                //logger("INFO", "Имя успешно прошло валидацию: " . htmlspecialchars($data['firstName']));
            }
        } else {
            //logger("INFO", "Имя не было передано или пустое. Пропуск валидации.");
        }

        // Проверка полного ФИО (пустое значение допустимо)
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
            } else {
                //logger("INFO", "Полное ФИО успешно прошло валидацию: " . htmlspecialchars($data['fullName']));
            }
        } else {
            //logger("INFO", "Полное ФИО не было передано или пустое. Пропуск валидации.");
        }

        // Проверка email (пустое значение допустимо)
        if (isset($data['email']) && $data['email'] !== "") {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $issue = 'Некорректный формат email.';
                $validationIssues[] = $issue;
                logger("WARNING", "Пользователь отправил некорректный email: " . htmlspecialchars($data['email']));
                echo json_encode(['success' => false, 'message' => $issue]);
                exit();
            } else {
                //logger("INFO", "Email успешно прошел валидацию: " . htmlspecialchars($data['email']));
            }
        } else {
            //logger("INFO", "Email не был передан или пустой. Пропуск валидации.");
        }

        // Проверка телефона (пустое значение допустимо)
        if (isset($data['phone']) && $data['phone'] !== "") {
            if (!preg_match('/^\+7\d{10}$/', $data['phone'])) {
                $issue = 'Некорректный формат телефона (ожидается +7XXXXXXXXXX).';
                $validationIssues[] = $issue;
                logger("WARNING", "Пользователь отправил некорректный телефон: " . htmlspecialchars($data['phone']));
                echo json_encode(['success' => false, 'message' => $issue]);
                exit();
            } else {
                //logger("INFO", "Телефон успешно прошел валидацию: " . htmlspecialchars($data['phone']));
            }
        } else {
            //logger("INFO", "Телефон не был передан или пустой. Пропуск валидации.");
        }

        // Проверка Telegram Username (пустое значение допустимо)
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
            } else {
                //logger("INFO", "Telegram Username успешно прошел валидацию: " . htmlspecialchars($data['telegramUsername']));
            }
        } else {
            //logger("INFO", "Telegram Username не был передан или пустой. Пропуск валидации.");
        }

        // Проверка Telegram ID (пустое значение допустимо)
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
            } else {
                //logger("INFO", "Telegram ID успешно прошел валидацию: " . htmlspecialchars($data['telegramID']));
            }
        } else {
            //logger("INFO", "Telegram ID не был передан или пустой. Пропуск валидации.");
        }

        // Если остались ошибки валидации, выводим их
        if (!empty($validationIssues)) {
            foreach ($validationIssues as $issue) {
                logger("WARNING", "Ошибка валидации: $issue");
            }
            logger("ERROR", "Валидация завершилась с ошибками. Отправка сообщения пользователю.");
            echo json_encode(['success' => false, 'message' => implode('. ', $validationIssues)]);
            exit();
        }

        //logger("INFO", "Все данные успешно прошли валидацию.");

        // Проверка CSRF-токена
        //logger("INFO", "Начало проверки CSRF-токена.");
        if (empty($data['csrf_token'])) {
            logger("ERROR", "CSRF-токен не передан в данных.");
            echo json_encode(['success' => false, 'message' => 'Ошибка безопасности: CSRF-токен не передан.']);
            exit();
        }
        if ($data['csrf_token'] !== $_SESSION['csrf_token']) {
            logger("ERROR", "CSRF-токен не совпадает с ожидаемым значением.");
            echo json_encode(['success' => false, 'message' => 'Ошибка безопасности: неверный CSRF-токен.']);
            exit();
        }
        //logger("INFO", "CSRF-токен успешно проверен.");

        try {
            // Подключение к базе данных
            //logger("INFO", "Подключение к базе данных...");
            $pdo = connectToDatabase();
            //logger("INFO", "Успешно подключено к базе данных.");

            // Обновление данных пользователя
           //logger("INFO", "Подготовка SQL-запроса для обновления данных пользователя.");
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

           //logger("INFO", "Выполнение SQL-запроса...");
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

            // Логирование результата выполнения запроса
            if ($stmt->rowCount() > 0) {
                logger("INFO", "Данные пользователя с UserID=" . htmlspecialchars($data['userID'] ?? 'не указано') . " успешно обновлены.");
            } else {
                logger("WARNING", "Обновление данных не затронуло ни одной записи. Возможно, UserID не найден.");
                echo json_encode(['success' => false, 'message' => 'Данные не были обновлены. Возможно, UserID не найден.']);
                exit();
            }

            echo json_encode(['success' => true, 'message' => 'Обновлено успешно']);
        } catch (PDOException $e) {
            // Подробное логирование ошибки базы данных
            logger("ERROR", "Произошла ошибка при работе с базой данных: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Ошибка базы данных.']);
            exit();
        } finally {
            // Закрытие соединения с базой данных (если необходимо)
            //logger("INFO", "Завершение работы с базой данных.");
        }
    }
?>