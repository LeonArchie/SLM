<?php
    // Проверка и определение ROOT_PATH
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
    }

    // Подключение function.php
    $file_path = ROOT_PATH . '/include/function.php';

    if (!file_exists($file_path)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка 0067: Ошибка сервера.']);
        exit();
    }

    require_once $file_path;

    // Запуск сессии
    startSessionIfNotStarted();

    header('Content-Type: text/event-stream; charset=utf-8');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    mb_internal_encoding('UTF-8');
    logger("DEBUG", "Установлены заголовки SSE");

    // Проверка CSRF-токена
    if ($_GET['csrf_token'] !== $_SESSION['csrf_token']) {
        logger("ERROR", "Неверный CSRF-токен.");
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Ошибка 0069: Обновите страницу и повторите попытку.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $taskId = uniqid('server_check_', true);
    $_SESSION['running_task'] = $taskId;
    logger("INFO", "Создана задача $taskId для поверки");

    try {      
        try {
            // Подключение к базе данных
            $pdo = connectToDatabase();
        } catch (Exception $e) {
            logger("ERROR", "Ошибка подключения к базе данных: " . $e->getMessage());
            http_response_code(500);
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
        performConflictChecks($servers, $taskId, $pdo);
        
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

        logger("DEBUG", "Отправлено SSE сообщение" . json_encode($payload, JSON_UNESCAPED_UNICODE));

        echo "data: " . json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n\n";
        flush();
    }

    /**
     * Выполнение проверок на конфликты с учетом уровней логирования
     */
    function performConflictChecks($servers, $taskId, $pdo) {
        // Проверка имен
        checkTaskInterruption($taskId);
        logger("INFO", "Начало проверки имен серверов");
        sendMessage('log', 'Проверка уникальности имен...');
        
        $nameResults = checkNameConflicts($servers);
        logCheckResults('Наменований серверов', $nameResults);
        if (!empty($nameResults['conflicts'])) {
            updatevalidateStatus($pdo, 'Name', $nameResults['conflicts']);
        }
        
        // Проверка ID
        checkTaskInterruption($taskId);
        logger("INFO", "Начало проверки ID серверов");
        sendMessage('log', 'Проверка уникальности ID...');
        
        $idResults = checkIdConflicts($servers);
        logCheckResults('id серверов', $idResults);
        if (!empty($idResults['conflicts'])) {
            updatevalidateStatus($pdo, 'serv_id', $idResults['conflicts']);
        }
        
        // Проверка IP
        checkTaskInterruption($taskId);
        logger("INFO", "Начало проверки IP адресов");
        sendMessage('log', 'Проверка уникальности IP...');
        
        $ipResults = checkIpConflicts($servers);
        logCheckResults('IP адресов', $ipResults);
        if (!empty($ipResults['conflicts'])) {
            updatevalidateStatus($pdo, 'ip_addr', $ipResults['conflicts']);
        }
    }

    /**
     * Обновление статуса validate в базе данных для конфликтующих записей
     */
    function updatevalidateStatus($pdo, $field, $conflictValues) {
        try {
            // Подготавливаем IN условие для запроса
            $placeholders = implode(',', array_fill(0, count($conflictValues), '?'));
            $query = "UPDATE servers SET \"validate\" = false WHERE \"$field\" IN ($placeholders)";
            
            $stmt = $pdo->prepare($query);
            
            // Выполняем запрос с передачей значений конфликтов
            if (!$stmt->execute($conflictValues)) {
                $errorInfo = $stmt->errorInfo();
                logger("ERROR", "Ошибка обновления статуса validate: " . $errorInfo[2]);
                sendMessage('done', 'Ошибка обновления статуса проверки в БД');
                return false;
            }
            
            $affectedRows = $stmt->rowCount();
            logger("INFO", "Обновлено записей в БД: $affectedRows (поле $field)");
            sendMessage('log', "Обновлено $affectedRows записей с конфликтами по полю $field");
            
            return true;
        } catch (Exception $e) {
            logger("ERROR", "Ошибка при обновлении validate: " . $e->getMessage());
            sendMessage('done', 'Ошибка при обновлении БД');
            return false;
        }
    }

    /**
     * Логирование результатов проверки с учетом уровней
     */
    function logCheckResults($checkType, $results) {
        if (!empty($results['conflicts'])) {
            logger("WARNING", "Обнаружены конфликты $checkType");
            sendMessage('warning', "Обнаружены дубликаты $checkType: " . implode(', ', $results['conflicts'])); 
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
        
        logger("DEBUG", "Результаты проверки имен: " . json_encode($conflicts));
        
        return ['conflicts' => $conflicts];
    }

    /**
     * Проверка конфликтов ID серверов
     */
    function checkIdConflicts($servers) {
        $ids = array_column($servers, 'serv_id');
        $counts = array_count_values($ids);
        $conflicts = array_keys(array_filter($counts, fn($count) => $count > 1));
        
        logger("DEBUG", "Результаты проверки ID: " . json_encode($conflicts));
        
        return ['conflicts' => $conflicts];
    }

    /**
     * Проверка конфликтов IP адресов
     */
    function checkIpConflicts($servers) {
        $ips = array_column($servers, 'ip_addr');
        $counts = array_count_values($ips);
        $conflicts = array_keys(array_filter($counts, fn($count) => $count > 1));
        
        logger("DEBUG", "Результаты проверки IP: " . json_encode($conflicts));
        
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