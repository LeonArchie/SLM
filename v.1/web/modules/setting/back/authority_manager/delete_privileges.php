<?php
    // Проверяем, определена ли константа ROOT_PATH. Если нет, определяем её как корневой путь сервера.
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
    }

    // Формируем путь к файлу function.php, который находится в папке include
    $file_path = ROOT_PATH . '/include/function.php';

    // Проверяем существование файла function.php. Если файл не существует, выводим ошибку и завершаем выполнение скрипта.
    if (!file_exists($file_path)) {
        echo json_encode(['success' => false, 'message' => 'Ошибка 0115: Ошибка сервера.']);
        exit();
    }

    // Подключаем файл function.php
    require_once $file_path;

    // Запускаем сессию, если она еще не была запущена
    startSessionIfNotStarted();

    try {
        // Подключаемся к базе данных
        $pdo = connectToDatabase();
    } catch (PDOException $e) {
        // Логируем ошибку подключения к базе данных
        logger("ERROR", "Ошибка подключения к базе данных: " . $e->getMessage());

        // Отправляем ошибку
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка 0116: Ошибка сервера.']);
        exit;
    }

    // Функция для проверки CSRF-токена
    function validateCsrfToken($token) {
        // Проверяем, совпадает ли переданный токен с токеном, хранящимся в сессии
        if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $token) {
            return false;
        }
        return true;
    }

    // Получаем данные из тела запроса в формате JSON
    $input = json_decode(file_get_contents('php://input'), true);

    // Проверяем наличие CSRF-токена в запросе и его валидность
    if (!isset($input['csrf_token']) || !validateCsrfToken($input['csrf_token'])) {
        http_response_code(400); // Устанавливаем код ответа 400 (Bad Request)
        echo json_encode(['success' => false, 'message' => 'Ошибка CSRF-токена']);
        logger("ERROR", "Ошибка CSRF-токена"); // Логируем ошибку
        exit;
    }

    // Проверяем, переданы ли привилегии для удаления
    if (!isset($input['privileges']) || empty($input['privileges'])) {
        http_response_code(400); // Устанавливаем код ответа 400 (Bad Request)
        echo json_encode(['success' => false, 'message' => 'Ошибка 0117: Обновите страницу и повторите попытку.']);
        logger("ERROR", "Привилегии для удаления не указаны"); // Логируем ошибку
        exit;
    }

    logger("INFO", "Получен запрос на удаление привилегий $input");
    audit("INFO", "Получен запрос на удаление привилегий $input");

    try {
        // Подготавливаем SQL-запрос для удаления записей из таблицы privileges
        $stmt = $pdo->prepare("DELETE FROM privileges WHERE id_privileges = :id");
        // Выполняем запрос для каждого ID привилегии
        foreach ($input['privileges'] as $id) {
            $stmt->execute(['id' => $id]);
        }

        // Подготавливаем SQL-запрос для удаления записей из таблицы name_privileges
        $stmt = $pdo->prepare("DELETE FROM name_privileges WHERE id_privileges = :id");
        // Выполняем запрос для каждого ID привилегии
        foreach ($input['privileges'] as $id) {
            $stmt->execute(['id' => $id]);
        }

        // Логируем успешное удаление привилегий
        logger("INFO", "Привилегии" . $input['privileges'] . " успешно удалены");
        audit("INFO", "Привилегии" . $input['privileges'] . " успешно удалены");

        // Отправляем успешный ответ
        http_response_code(200);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        // Логируем ошибку, если возникло исключение при удалении привилегий
        logger("ERROR", "Ошибка при удалении привилегий: " . $e->getMessage());

        // Устанавливаем код ответа 500 (Internal Server Error) и отправляем сообщение об ошибке
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка 0118: Ошибка сервера.']);
    }
?>