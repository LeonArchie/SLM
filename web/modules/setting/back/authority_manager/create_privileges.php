<?php
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
    }
    $file_path = ROOT_PATH . '/include/function.php';

    // Проверяем существование файла function.php
    if (!file_exists($file_path)) {
        logger("ERROR", "Файл function.php не найден.");
        echo json_encode(['success' => false, 'message' => 'Ошибка сервера: файл function.php не найден.']);
        exit();
    }

    require_once $file_path;

    // Запуск сессии, если она еще не запущена
    startSessionIfNotStarted();

    try {
        logger("INFO", "Обработка запроса на создание привилегии.");

        $pdo = connectToDatabase();

        $data = json_decode(file_get_contents('php://input'), true);

        // Проверка наличия обязательных данных
        if (empty($data['csrf_token']) || empty($data['privilegeName']) || empty($data['privilegeID'])) {
            logger("ERROR", "В полученном запросе отсутствуют обязательные поля: csrf_token, privilegeName или privilegeID.");
            http_response_code(400); // Bad Request
            echo json_encode(['success' => false, 'message' => 'Недостаточно данных для выполнения запроса.']);
            exit;
        }

        // Проверка CSRF-токена
        logger("INFO", "Проверка CSRF-токена.");
        if ($data['csrf_token'] !== $_SESSION['csrf_token']) {
            logger("ERROR", "Неверный CSRF-токен. Ожидался: " . $_SESSION['csrf_token'] . ", получен: " . $data['csrf_token']);
            http_response_code(403); // Forbidden
            echo json_encode(['success' => false, 'message' => 'Неверный CSRF-токен.']);
            exit;
        }

        // Проверка уникальности privilegeID
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM name_privileges WHERE id_privileges = ?");
        $stmt->execute([$data['privilegeID']]);
        if ($stmt->fetchColumn() > 0) {
            logger("ERROR", "Привилегия с таким ID уже существует: " . $data['privilegeID']);
            http_response_code(400); // Bad Request
            echo json_encode(['success' => false, 'message' => 'Привилегия с таким ID уже существует.']);
            exit;
        }

        // Проверка уникальности имени привилегии
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM name_privileges WHERE name_privileges = ?");
        $stmt->execute([$data['privilegeName']]);
        if ($stmt->fetchColumn() > 0) {
            logger("ERROR", "Привилегия с таким именем уже существует: " . $data['privilegeName']);
            http_response_code(400); // Bad Request
            echo json_encode(['success' => false, 'message' => 'Привилегия с таким именем уже существует.']);
            exit;
        }

        //Регистрируем привилегию
        $insertStmt = $pdo->prepare("INSERT INTO name_privileges (name_privileges, id_privileges) VALUES (?, ?)");
        $insertStmt->execute([
            $data['privilegeName'],
            $data['privilegeID']
        ]);
        logger("INFO", "Новая привилегия успешно создана: " . $data['privilegeName'] . " (ID: " . $data['privilegeID'] . ")");

        // Возвращаем успешный ответ
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        // Логирование ошибки базы данных
        logger("ERROR", "Ошибка базы данных: " . $e->getMessage());
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'message' => 'Ошибка базы данных.']);
    } catch (Exception $e) {
        // Логирование других ошибок
        logger("ERROR", "Ошибка: " . $e->getMessage());
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'message' => 'Внутренняя ошибка сервера.']);
    }
?>