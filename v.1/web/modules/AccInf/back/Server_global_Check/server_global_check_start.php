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

    // Устанавливаем правильные заголовки
    header('Content-Type: text/event-stream; charset=utf-8');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    
    // Проверка CSRF
    if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
        sendMessage('error', 'Неверный CSRF токен');
        exit;
    }
    
    $taskId = uniqid();
    $_SESSION['running_task'] = $taskId;
    
    // Функция для отправки сообщений
    function sendMessage($type, $message) {
        $data = [
            'type' => $type,
            'message' => $message
        ];
        echo "data: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
        ob_flush();
        flush();
    }
    
    try {
        // Подключение к БД
        $pdo = connectToDatabase();
        sendMessage('success', 'Подключение к базе данных успешно');
        
        // Получаем данные серверов
        $stmt = $pdo->prepare('SELECT servers."Name", Status, serv_id, ip_addr, servers."Domain", servers."Demon" FROM servers');
        if (!$stmt->execute()) {
            sendMessage('error', 'Ошибка выполнения запроса к таблице servers');
            exit;
        }
        
        $servers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendMessage('log', 'Получено ' . count($servers) . ' серверов для анализа');
        
        // Проверка Name
        sendMessage('log', 'Проверка уникальности имен серверов...');
        $nameResults = checkNameConflicts($servers);
        if (!empty($nameResults['conflicts'])) {
            sendMessage('warning', 'Обнаружены дубликаты имен: ' . implode(', ', $nameResults['conflicts']));
        } else {
            sendMessage('success', 'Конфликты имен не обнаружены');
        }
        
        // Проверка ID
        if ($_SESSION['running_task'] !== $taskId) {
            sendMessage('error', 'Проверка отменена пользователем');
            exit;
        }
        sendMessage('log', 'Проверка уникальности ID серверов...');
        $idResults = checkIdConflicts($servers);
        if (!empty($idResults['conflicts'])) {
            sendMessage('warning', 'Обнаружены дубликаты ID: ' . implode(', ', $idResults['conflicts']));
        } else {
            sendMessage('success', 'Конфликты ID не обнаружены');
        }
        
        // Проверка IP
        if ($_SESSION['running_task'] !== $taskId) {
            sendMessage('error', 'Проверка отменена пользователем');
            exit;
        }
        sendMessage('log', 'Проверка уникальности IP адресов...');
        $ipResults = checkIpConflicts($servers);
        if (!empty($ipResults['conflicts'])) {
            sendMessage('warning', 'Обнаружены дубликаты IP: ' . implode(', ', $ipResults['conflicts']));
        } else {
            sendMessage('success', 'Конфликты IP не обнаружены');
        }
        
        sendMessage('success', 'Проверка завершена успешно');
        
    } catch (PDOException $e) {
        sendMessage('error', 'Ошибка базы данных: ' . $e->getMessage());
    } finally {
        unset($_SESSION['running_task']);
    }
    
    function checkNameConflicts($servers) {
        $names = array_column($servers, 'Name');
        $conflicts = array_diff_assoc($names, array_unique($names));
        return ['conflicts' => array_unique($conflicts)];
    }
    
    function checkIdConflicts($servers) {
        $ids = array_column($servers, 'serv_id');
        $conflicts = array_diff_assoc($ids, array_unique($ids));
        return ['conflicts' => array_unique($conflicts)];
    }
    
    function checkIpConflicts($servers) {
        $ips = array_column($servers, 'ip_addr');
        $conflicts = array_diff_assoc($ips, array_unique($ips));
        return ['conflicts' => array_unique($conflicts)];
    }
?>