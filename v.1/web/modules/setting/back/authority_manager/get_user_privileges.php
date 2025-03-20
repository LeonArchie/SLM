<?php
    // Проверяем, определена ли константа ROOT_PATH, и если нет, определяем её
    // ROOT_PATH используется для указания корневого пути проекта
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
    }

    // Формируем путь к файлу function.php, который находится в папке include
    $file_path = ROOT_PATH . '/include/function.php';

    // Проверяем существование файла function.php
    // Если файл не найден, выводим JSON-ответ с ошибкой и завершаем выполнение скрипта
    if (!file_exists($file_path)) {
        echo json_encode(['success' => false, 'message' => 'Ошибка 0121: Ошибка сервера.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Подключаем файл function.php, который содержит необходимые функции
    require_once $file_path;

    // Запускаем сессию, если она ещё не запущена
    // Функция startSessionIfNotStarted() должна быть определена в function.php
    startSessionIfNotStarted();

    // Получаем userID из GET-запроса и удаляем лишние пробелы
    $userID = isset($_GET['userID']) ? trim($_GET['userID']) : '';

    // Проверяем, что userID не пустой
    // Если userID пустой, логируем ошибку и выводим JSON-ответ с ошибкой
    if (empty($userID)) {
        logger("ERROR", "Неверный UserID: " . $userID);
        echo json_encode(['success' => false, 'message' => 'Ошибка 0122: UserID несуществует.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Пытаемся подключиться к базе данных
    // Функция connectToDatabase() должна быть определена в function.php
    try {
        $pdo = connectToDatabase();
    } catch (PDOException $e) {
        // В случае ошибки подключения логируем ошибку и выводим JSON-ответ с ошибкой
        logger("ERROR", "Ошибка подключения к базе данных: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Ошибка 0123: Ошибка сервера.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Пытаемся выполнить запрос к таблице privileges для получения данных по userID
    try {
        $stmt = $pdo->prepare("SELECT * FROM privileges WHERE userid = :userID");
        $stmt->execute(['userID' => $userID]);
        $privileges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // В случае ошибки выполнения запроса логируем ошибку и выводим JSON-ответ с ошибкой
        logger("ERROR", "Ошибка при выполнении запроса к таблице privileges: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Ошибка 0124: Ошибка сервера.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Формируем массив privileges_id, который будет содержать id_privileges
    $privileges_id = [];
    foreach ($privileges as $privilege) {
        // Проверяем, что id_privileges не пустой и добавляем его в массив
        if (!empty($privilege['id_privileges']) && $privilege['id_privileges'] !== '') {
            $privileges_id[] = $privilege['id_privileges'];
        }
    }

    // Экранируем значения для SQL-запроса, добавляя кавычки вокруг каждого id_privileges
    $privileges_id = array_map(function ($id) {
        return "'" . $id . "'";
    }, $privileges_id);

    // Проверяем, если массив privileges_id пустой, возвращаем сообщение об отсутствии данных
    if (empty($privileges_id)) {
        logger("WARNING", "Нет данных о полномочиях для UserID: " . $userID);
        echo json_encode(['success' => false, 'message' => 'Ошибка 0125: Нет данных о полномочиях данного пользователя.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Пытаемся выполнить запрос к таблице name_privileges для получения названий привилегий
    try {
        $stmt = $pdo->prepare("SELECT * FROM name_privileges WHERE id_privileges IN (".implode(',', $privileges_id).")");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // В случае ошибки выполнения запроса логируем ошибку и выводим JSON-ответ с ошибкой
        logger("ERROR", "Ошибка при выполнении запроса к таблице name_privileges: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Ошибка 0126: Ошибка сервера.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Генерация HTML-таблицы для отображения данных
    $html = '<table id="privilegesTable">
        <thead>
            <tr>
                <th>ИД</th>
                <th>Привилегия</th>
            </tr>
        </thead>
        <tbody>';

    // Если данных нет, выводим сообщение "Нет данных"
    if (empty($data)) {
        $html .= '<tr><td colspan="3">Нет данных</td></tr>';
    } else {
        // Иначе выводим данные в таблицу, экранируя специальные символы с помощью htmlspecialchars
        foreach ($data as $privilege) {
            $html .= '<tr>
                <td>' . htmlspecialchars($privilege['id_privileges']) . '</td>
                <td>' . htmlspecialchars($privilege['name_privileges']) . '</td>
            </tr>';
        }
    }

    $html .= '</tbody></table>';
    
    // Возвращаем HTML-таблицу в ответе
    header('Content-Type: text/html');
    echo $html;
?>