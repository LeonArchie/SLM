<?php
    // Проверка и определение ROOT_PATH
    // Если константа ROOT_PATH не определена, задаем её как корневой путь сервера.
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
    }

    // Подключение function.php
    // Формируем путь к файлу function.php, который находится в папке include.
    $file_path = ROOT_PATH . '/include/function.php';

    // Проверяем, существует ли файл function.php. Если нет, возвращаем ошибку и завершаем выполнение.
    if (!file_exists($file_path)) {
        http_response_code(500); // Устанавливаем HTTP-код 500 (Internal Server Error).
        echo json_encode(['success' => false, 'message' => 'Ошибка 0067: Ошибка сервера.']);
        exit();
    }

    // Подключаем файл function.php, который содержит необходимые функции.
    require_once $file_path;

    // Запуск сессии
    // Если сессия не была запущена, запускаем её.
    startSessionIfNotStarted();

    header('Content-Type: text/event-stream; charset=utf-8');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    mb_internal_encoding('UTF-8');
    logger("DEBUG", "Установлены заголовки SSE");

    // Проверка CSRF-токена
    // Сравниваем CSRF-токен из запроса с токеном, хранящимся в сессии.
    if ($_GET['csrf_token'] !== $_SESSION['csrf_token']) {
        logger("ERROR", "Неверный CSRF-токен."); // Логируем ошибку.
        http_response_code(403); // Устанавливаем HTTP-код 403 (Forbidden).
        echo json_encode(['success' => false, 'message' => 'Ошибка 0069: Обновите страницу и повторите попытку.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $taskId = uniqid('server_check_', true);
    $_SESSION['running_task'] = $taskId;
    logger("INFO", "Создана задача $taskId для поверки");

    try {      
        try {
            // Пытаемся подключиться к базе данных.
            $pdo = connectToDatabase();
        } catch (Exception $e) {
            // Если подключение не удалось, логируем ошибку и завершаем выполнение.
            logger("ERROR", "Ошибка подключения к базе данных: " . $e->getMessage());
            http_response_code(500); // Устанавливаем HTTP-код 500 (Internal Server Error).
            echo json_encode(['success' => false, 'message' => 'Ошибка 0071: Ошибка сервера.'], JSON_UNESCAPED_UNICODE);
            exit();
        }
        
        // Получение списка серверов
        $query = 'SELECT servers."Name", Status, serv_id, ip_addr, servers."Domain", servers."Demon" FROM servers';
        $stmt = $pdo->prepare($query);
        
        if (!$stmt->execute()) {
            $errorInfo = $stmt->errorInfo();
            logger("ERROR", "Ошибка выполнения SQL запроса");
            sendMessage('error', 'Ошибка выполнения запроса к БД');
            exit();
        }
        
        $servers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $serverCount = count($servers);
        logger("INFO", "Получено серверов для анализа: " . $serverCount);
        sendMessage('log', 'Получено серверов для анализа: ' . $serverCount);
        
        // Проверки конфликтов
        performConflictChecks($servers, $taskId);
        
        logger("INFO", "Проверка серверов успешно завершена", [
            'execution_time' => round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 2) . ' сек'
        ]);
        sendMessage('done', 'Проверка завершена успешно');
        
    } catch (Exception $e) {
        logger("ERROR", "Неожиданная ошибка: " . $e->getMessage());
        sendMessage('done', 'Неожиданная ошибка');
    } finally {
        unset($_SESSION['running_task']);
        logger("DEBUG", "Завершение задачи", ['task_id' => $taskId]);
    }

    /**
     * Отправка сообщения через SSE соединение
     */
    function sendMessage($type, $message) {
        $payload = [
            'type' => $type,
            'message' => $message,
            'timestamp' => time()
        ];

        logger("DEBUG", "Отправлено SSE сообщение" . $payload);

        echo "data: " . json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n\n";
        flush();
    }

    /**
     * Выполнение проверок на конфликты с учетом уровней логирования
     */
    function performConflictChecks($servers, $taskId) {
        // Проверка имен
        checkTaskInterruption($taskId);
        logger("INFO", "Начало проверки имен серверов");
        sendMessage('log', 'Проверка уникальности имен...');
        
        $nameResults = checkNameConflicts($servers);
        logCheckResults('Наменований серверов', $nameResults);
        
        // Проверка ID
        checkTaskInterruption($taskId);
        logger("INFO", "Начало проверки ID серверов");
        sendMessage('log', 'Проверка уникальности ID...');
        
        $idResults = checkIdConflicts($servers);
        logCheckResults('id серверов', $idResults);
        
        // Проверка IP
        checkTaskInterruption($taskId);
        logger("INFO", "Начало проверки IP адресов");
        sendMessage('log', 'Проверка уникальности IP...');
        
        $ipResults = checkIpConflicts($servers);
        logCheckResults('IP адресов', $ipResults);
    }

    /**
     * Логирование результатов проверки с учетом уровней
     */
    function logCheckResults($checkType, $results) {
        if (!empty($results['conflicts'])) {
            logger("WARNING", "Обнаружены конфликты $checkType");
            sendMessage('warning', "Обнаружены дубликаты $checkType " . implode(', ', $results['conflicts'])); 
        } else {
            logger("INFO", "Конфликты $checkType не обнаружены");
            sendMessage('success', "Конфликты $checkType не обнаружены");
        }
    }

    /**
     * Проверка конфликтов имен серверов
     */
    function checkNameConflicts($servers) {
        $names = array_column($servers, 'name');
        $counts = array_count_values($names);
        $conflicts = array_keys(array_filter($counts, fn($count) => $count > 1));
        
        logger("DEBUG", "Результаты проверки имен". $conflicts);
        
        return ['conflicts' => $conflicts];
    }

    /**
     * Проверка конфликтов ID серверов
     */
    function checkIdConflicts($servers) {
        $ids = array_column($servers, 'serv_id');
        $counts = array_count_values($ids);
        $conflicts = array_keys(array_filter($counts, fn($count) => $count > 1));
        
        logger("DEBUG", "Результаты проверки ID". $conflicts);
        
        return ['conflicts' => $conflicts];
    }

    /**
     * Проверка конфликтов IP адресов
     */
    function checkIpConflicts($servers) {
        $ips = array_column($servers, 'ip_addr');
        $counts = array_count_values($ips);
        $conflicts = array_keys(array_filter($counts, fn($count) => $count > 1));
        
        logger("DEBUG", "Результаты проверки IP". $conflicts);
        
        return ['conflicts' => $conflicts];
    }

    /**
     * Проверка прерывания задачи
     */
    function checkTaskInterruption($taskId) {
        if (!isset($_SESSION['running_task']) || $_SESSION['running_task'] !== $taskId) {
            logger("WARNING", "Задача $taskId прервана пользователем");
            sendMessage('error', 'Проверка отменена пользователем');
            exit;
        }
    }
?>