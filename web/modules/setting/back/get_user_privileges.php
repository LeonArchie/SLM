<?php
define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
$file_path = ROOT_PATH . '/include/function.php';

// Проверяем существование файла function.php
if (!file_exists($file_path)) {
    logger("ERROR", "Файл function.php не найден.");
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера: файл function.php не найден.'], JSON_UNESCAPED_UNICODE);
    exit();
}

require_once $file_path;

startSessionIfNotStarted();

// Получаем UserID из запроса
$userID = isset($_GET['userID']) ? trim($_GET['userID']) : '';
logger("INFO", "Получен UserID: " . $userID);

if (empty($userID)) {
    logger("ERROR", "Неверный UserID: " . $userID);
    echo json_encode(['success' => false, 'message' => 'Неверный UserID.'], JSON_UNESCAPED_UNICODE);
    exit();
}

// Подключаемся к базе данных
try {
    $pdo = connectToDatabase();
    logger("INFO", "Подключение к базе данных успешно установлено.");
} catch (PDOException $e) {
    logger("ERROR", "Ошибка подключения к базе данных: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка подключения к базе данных.'], JSON_UNESCAPED_UNICODE);
    exit();
}

// Запрос к таблице privileges для получения данных
try {
    $stmt = $pdo->prepare("SELECT * FROM privileges WHERE userid = :userID");
    $stmt->execute(['userID' => $userID]);
    $privileges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    logger("INFO", "Данные из таблицы privileges: " . print_r($privileges, true));
} catch (PDOException $e) {
    logger("ERROR", "Ошибка при выполнении запроса к таблице privileges: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка при получении данных.'], JSON_UNESCAPED_UNICODE);
    exit();
}

// Собираем массивы modules_id и pages_id
$modulesIds = [];
$pagesIds = [];
foreach ($privileges as $privilege) {
    // Добавляем module_id, если он не пустой
    if (!empty($privilege['module_id']) && $privilege['module_id'] !== '') {
        $modulesIds[] = $privilege['module_id'];
    }
    // Добавляем pages_id, если он не пустой
    if (!empty($privilege['pages_id']) && $privilege['pages_id'] !== '') {
        $pagesIds[] = $privilege['pages_id'];
    }
}

// Экранируем значения для SQL-запроса
$modulesIds = array_map(function ($id) {
    return "'" . $id . "'";
}, $modulesIds);

$pagesIds = array_map(function ($id) {
    return "'" . $id . "'";
}, $pagesIds);

logger("INFO", "Собранные modules_id: " . print_r($modulesIds, true));
logger("INFO", "Собранные pages_id: " . print_r($pagesIds, true));

// Если массивы пустые, возвращаем сообщение
if (empty($modulesIds) && empty($pagesIds)) {
    logger("WARNING", "Нет данных о полномочиях для UserID: " . $userID);
    echo json_encode(['success' => false, 'message' => 'Нет данных о полномочиях для данного пользователя.'], JSON_UNESCAPED_UNICODE);
    exit();
}

// Запрос к таблице name_privileges для получения данных
try {
    $stmt = $pdo->prepare("SELECT * FROM name_privileges WHERE id_privileges IN (".implode(',', array_merge($modulesIds, $pagesIds)).")");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    logger("INFO", "Данные из таблицы name_privileges: " . print_r($data, true));
} catch (PDOException $e) {
    logger("ERROR", "Ошибка при выполнении запроса к таблице name_privileges: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка при получении данных.'], JSON_UNESCAPED_UNICODE);
    exit();
}

// Генерация HTML-таблицы
$html = '<table id="privilegesTable">
    <thead>
        <tr>
            <th>ИД</th>
            <th>Привилегия</th>
            <th>Pages</th>
        </tr>
    </thead>
    <tbody>';

if (empty($data)) {
    $html .= '<tr><td colspan="3">Нет данных</td></tr>';
} else {
    foreach ($data as $privilege) {
        $html .= '<tr>
            <td>' . htmlspecialchars($privilege['id_privileges']) . '</td>
            <td>' . htmlspecialchars($privilege['name_privileges']) . '</td>
            <td><input type="checkbox" ' . ($privilege['pages'] ? 'checked' : '') . ' disabled></td>
        </tr>';
    }
}

$html .= '</tbody></table>';

// Возвращаем HTML
header('Content-Type: text/html');
echo $html;
?>