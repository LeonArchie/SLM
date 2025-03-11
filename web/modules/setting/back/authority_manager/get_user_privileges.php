<?php
    // Проверяем, определена ли константа ROOT_PATH, и если нет, определяем её
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
    }
    // Формируем путь к файлу function.php
    $file_path = ROOT_PATH . '/include/function.php';

    // Проверяем существование файла function.php
    if (!file_exists($file_path)) {
        logger("ERROR", "Файл function.php не найден.");
        echo json_encode(['success' => false, 'message' => 'Ошибка сервера: файл function.php не найден.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Подключаем файл function.php
    require_once $file_path;

    // Запускаем сессию, если она ещё не запущена
    startSessionIfNotStarted();

    // Получаем userID из GET-запроса
    $userID = isset($_GET['userID']) ? trim($_GET['userID']) : '';

    // Проверяем, что userID не пустой
    if (empty($userID)) {
        logger("ERROR", "Неверный UserID: " . $userID);
        echo json_encode(['success' => false, 'message' => 'Неверный UserID.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Пытаемся подключиться к базе данных
    try {
        $pdo = connectToDatabase();
    } catch (PDOException $e) {
        logger("ERROR", "Ошибка подключения к базе данных: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Ошибка подключения к базе данных.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Пытаемся выполнить запрос к таблице privileges
    try {
        $stmt = $pdo->prepare("SELECT * FROM privileges WHERE userid = :userID");
        $stmt->execute(['userID' => $userID]);
        $privileges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        logger("ERROR", "Ошибка при выполнении запроса к таблице privileges: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Ошибка при получении данных.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Формируем массив privileges_id
    $privileges_id = [];
    foreach ($privileges as $privileges_id) {
        if (!empty($privilege['id_privileges']) && $privilege['id_privileges'] !== '') {
            $privileges_id[] = $privilege['id_privileges'];
        }
    }

    // Экранируем значения для SQL-запроса
    $privileges_id = array_map(function ($id) {
        return "'" . $id . "'";
    }, $privileges_id);

    // Проверяем, если массивы пустые, возвращаем сообщение
    if (empty($privileges_id) && empty($pagesIds)) {
        logger("WARNING", "Нет данных о полномочиях для UserID: " . $userID);
        echo json_encode(['success' => false, 'message' => 'Нет данных о полномочиях для данного пользователя.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Пытаемся выполнить запрос к таблице name_privileges
    try {
        $stmt = $pdo->prepare("SELECT * FROM name_privileges WHERE id_privileges IN (".implode(',', array_merge($privileges_id)).")");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    // Если данных нет, выводим сообщение
    if (empty($data)) {
        $html .= '<tr><td colspan="3">Нет данных</td></tr>';
    } else {
        // Иначе выводим данные в таблицу
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