<?php
// Проверяем, определена ли константа ROOT_PATH. Если нет, определяем её.
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
}

// Подключаем необходимые файлы
require_once ROOT_PATH . '/include/function.php';

// Запускаем сессию, если она ещё не начата
startSessionIfNotStarted();

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен', 'keepEditMode' => true]);
    exit();
}

// Проверяем авторизацию пользователя
checkAuth();

// Проверяем, что запрос пришел от кнопки Сохранить
$input = json_decode(file_get_contents('php://input'), true);
if (($input['source'] ?? '') !== 'save_button') {
    http_response_code(403);
    logger("ERROR", "Попытка сохранения из неавторизованного источника");
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен', 'keepEditMode' => true]);
    exit();
}

// Проверяем валидность данных
if (json_last_error() !== JSON_ERROR_NONE || !isset($input['menu'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Неверный формат данных', 'keepEditMode' => true]);
    exit();
}

// Путь к файлу с модулями
$filePath = ROOT_PATH . '/config/modules.json';

// Проверяем возможность записи в файл
if (!is_writable($filePath)) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Файл модулей недоступен для записи',
        'keepEditMode' => true
    ]);
    exit();
}

// Сохраняем данные в файл
try {
    $result = file_put_contents(
        $filePath,
        json_encode(['menu' => $input['menu']], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

    if ($result === false) {
        throw new Exception('Ошибка записи в файл');
    }

    // Логируем успешное сохранение
    logger("INFO", "Модули успешно обновлены");

    echo json_encode([
        'success' => true, 
        'message' => 'Изменения сохранены',
        'keepEditMode' => false
    ]);
} catch (Exception $e) {
    http_response_code(500);
    logger("ERROR", "Ошибка сохранения модулей: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Ошибка сохранения: ' . $e->getMessage(),
        'keepEditMode' => true
    ]);
}
?>