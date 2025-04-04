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

    // Установка кодировки
    header('Content-Type: application/json; charset=utf-8');
    mb_internal_encoding('UTF-8');

    if (isset($_SESSION['running_task'])) {
        unset($_SESSION['running_task']);
        echo json_encode([
            'status' => 'success',
            'message' => 'Проверка остановлена'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Нет активных задач для остановки'
        ], JSON_UNESCAPED_UNICODE);
    }
?>