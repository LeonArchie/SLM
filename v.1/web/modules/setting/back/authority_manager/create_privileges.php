<?php
    // Проверяем, определена ли константа ROOT_PATH. Если нет, определяем её как корневой путь документа.
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
    }

    // Формируем путь к файлу function.php, который находится в папке include
    $file_path = ROOT_PATH . '/include/function.php';

    // Проверяем существование файла function.php
    if (!file_exists($file_path)) {
        // Если файл не найден, логируем ошибку и возвращаем JSON-ответ с сообщением об ошибке
        logger("ERROR", "Файл function.php не найден.");
        echo json_encode(['success' => false, 'message' => 'Ошибка 0108: Ошибка сервера.']);
        exit(); // Прекращаем выполнение скрипта
    }

    // Подключаем файл function.php
    require_once $file_path;

    // Запускаем сессию, если она еще не запущена
    startSessionIfNotStarted();

    try {
        // Логируем начало обработки запроса на создание привилегии
        logger("INFO", "Начало обработки запроса на создание привилегии.");

        // Устанавливаем соединение с базой данных
        $pdo = connectToDatabase();

        // Получаем данные из тела запроса в формате JSON
        $data = json_decode(file_get_contents('php://input'), true);

        logger("INFO", "Обработка запроса на создание привилегии. Параметры: $data");
        audit("INFO", "Обработка запроса на создание привилегии. Параметры: $data");

        // Проверяем наличие обязательных полей в данных: csrf_token, privilegeName и privilegeID
        if (empty($data['csrf_token']) || empty($data['privilegeName']) || empty($data['privilegeID'])) {
            // Если какое-то из полей отсутствует, логируем ошибку и возвращаем JSON-ответ с сообщением об ошибке
            logger("ERROR", "В полученном запросе отсутствуют обязательные поля: csrf_token, privilegeName или privilegeID.");
            http_response_code(400); // Устанавливаем код ответа 400 (Bad Request)
            echo json_encode(['success' => false, 'message' => 'Ошибка 0109: Отсутствуют обязательные параметры.']);
            exit; // Прекращаем выполнение скрипта
        }

        // Проверяем CSRF-токен на соответствие токену, хранящемуся в сессии
        logger("INFO", "Проверка CSRF-токена.");
        if ($data['csrf_token'] !== $_SESSION['csrf_token']) {
            // Если токены не совпадают, логируем ошибку и возвращаем JSON-ответ с сообщением об ошибке
            logger("ERROR", "Неверный CSRF-токен.");
            http_response_code(403); // Устанавливаем код ответа 403 (Forbidden)
            echo json_encode(['success' => false, 'message' => 'Ошибка 0110: Обновите страницу и повторите попытку.']);
            exit; // Прекращаем выполнение скрипта
        }

        // Проверяем уникальность privilegeID и privilegeName в одном запросе
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM name_privileges WHERE id_privileges = ? OR name_privileges = ?");
        $stmt->execute([$data['privilegeID'], $data['privilegeName']]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            // Если привилегия с таким ID или именем уже существует, логируем ошибку и возвращаем JSON-ответ с сообщением об ошибке
            logger("ERROR", "Привилегия с таким ID или именем уже существует: ID - " . $data['privilegeID'] . ", Имя - " . $data['privilegeName']);
            http_response_code(400); // Устанавливаем код ответа 400 (Bad Request)
            echo json_encode(['success' => false, 'message' => 'Ошибка 0112: Привилегия с таким ID или именем уже существует.']);
            exit; // Прекращаем выполнение скрипта
        }

        // Вставляем новую привилегию в таблицу name_privileges
        $insertStmt = $pdo->prepare("INSERT INTO name_privileges (name_privileges, id_privileges) VALUES (?, ?)");
        $insertStmt->execute([
            $data['privilegeName'],
            $data['privilegeID']
        ]);
        // Логируем успешное создание привилегии
        logger("INFO", "Новая привилегия успешно создана: " . $data['privilegeName'] . " (ID: " . $data['privilegeID'] . ")");
        audit("INFO", "Новая привилегия успешно создана: " . $data['privilegeName'] . " (ID: " . $data['privilegeID'] . ")");

        // Возвращаем успешный JSON-ответ
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        // Логируем ошибку базы данных и возвращаем JSON-ответ с сообщением об ошибке
        logger("ERROR", "Ошибка базы данных: " . $e->getMessage());
        http_response_code(500); // Устанавливаем код ответа 500 (Internal Server Error)
        echo json_encode(['success' => false, 'message' => 'Ошибка 0113: Произошла неизвестная ошибка.']);
    } catch (Exception $e) {
        // Логируем другие ошибки и возвращаем JSON-ответ с сообщением об ошибке
        logger("ERROR", "Ошибка: " . $e->getMessage());
        http_response_code(500); // Устанавливаем код ответа 500 (Internal Server Error)
        echo json_encode(['success' => false, 'message' => 'Ошибка 0114: Произошла неизвестная ошибка.']);
    }
?>