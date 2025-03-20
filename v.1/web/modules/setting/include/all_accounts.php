<?
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

    /**
     * Создание пользователя.
     *
     * @param PDO $pdo Объект PDO для подключения к базе данных.
     * @param array $data Данные пользователя.
     * @return array Результат операции.
     */
    function createUser($pdo, $data) {
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
                http_response_code(409); // Конфликт (уже существует)
                logger("ERROR", "Пользователь с таким логином или email уже существует: " . htmlspecialchars($userlogin) . ", " . htmlspecialchars($email));
                return ['success' => false, 'http_code' => 409, 'message' => 'Ошибка 0080: Пользователь с таким логином или email уже существует.'];
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


            // Успешный ответ
            logger("INFO", "Пользователь успешно создан: " . json_encode([
                'userid' => $userid,
                'userlogin' => $userlogin,
                'email' => $email,
                'ip' => $_SERVER['REMOTE_ADDR'],
                'timestamp' => $currentTime
            ]));
            audit("INFO", "Пользователь успешно создан: " . json_encode([
                'userid' => $userid,
                'userlogin' => $userlogin,
                'email' => $email,
                'ip' => $_SERVER['REMOTE_ADDR'],
                'timestamp' => $currentTime
            ]));
            return ['success' => true, 'http_code' => 200, 'message' => 'Пользователь успешно создан.'];
        } catch (Exception $e) {
            // Откат транзакции в случае ошибки
            $pdo->rollBack();
            logger("ERROR", "Ошибка при создании пользователя: " . json_encode([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]));
            return ['success' => false, 'http_code' => 500, 'message' => 'Ошибка 0081: Ошибка сервера.'];
        }
    }

/**
 * Удаляет пользователей из базы данных по их ID, а также удаляет связанные записи из таблицы privileges.
 *
 * @param PDO $pdo Объект PDO для подключения к базе данных.
 * @param array $userIds Массив ID пользователей, которых нужно удалить.
 * @return bool Возвращает true, если удаление прошло успешно, иначе false.
 */
function deleteUsers($pdo, $userIds) {
    // Экранируем и оборачиваем в кавычки каждый ID пользователя из массива user_ids
    $escapedUserIds = array_map(function($id) use ($pdo) {
        return $pdo->quote($id);
    }, $userIds);

    // Формируем SQL-запрос для удаления пользователей с указанными ID
    $sqlUsers = "DELETE FROM users WHERE userid IN (" . implode(',', $escapedUserIds) . ")";
    $stmtUsers = $pdo->prepare($sqlUsers);

    // Формируем SQL-запрос для удаления записей из таблицы privileges с указанными ID пользователей
    $sqlPrivileges = "DELETE FROM privileges WHERE userid IN (" . implode(',', $escapedUserIds) . ")";
    $stmtPrivileges = $pdo->prepare($sqlPrivileges);

    // Начинаем транзакцию
    $pdo->beginTransaction();

    try {
        // Выполняем удаление из таблицы privileges
        $stmtPrivileges->execute();

        // Выполняем удаление из таблицы users
        $stmtUsers->execute();

        // Фиксируем транзакцию
        $pdo->commit();

        return true;
    } catch (Exception $e) {
        // Откатываем транзакцию в случае ошибки
        $pdo->rollBack();
        return false;
    }
}
?>