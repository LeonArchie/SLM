<?php
    // Проверяем, определена ли константа ROOT_PATH. Если нет, определяем её как корневой путь сервера.
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
    }

    // Формируем путь к файлу function.php, который находится в папке include
    $file_path = ROOT_PATH . '/include/function.php';

    // Проверяем существование файла function.php. Если файл не найден, выводим JSON-ответ с ошибкой и завершаем выполнение скрипта.
    if (!file_exists($file_path)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка 0119: Ошибка сервера.']);
        exit();
    }

    // Подключаем файл function.php, который содержит необходимые функции
    require_once $file_path;

    // Запускаем сессию, если она ещё не была запущена
    startSessionIfNotStarted();

    // Пытаемся подключиться к базе данных
    try {
        $pdo = connectToDatabase();
    } catch (PDOException $e) {
        // Если произошла ошибка при подключении, выводим сообщение об ошибке и завершаем выполнение скрипта
        http_response_code(500);
        logger("ERROR", "Ошибка подключения к базе данных: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Ошибка 0120: Ошибка сервера.']);
        exit();
    }

    // Подготавливаем SQL-запрос для выборки всех данных из таблицы name_privileges
    $stmt = $pdo->prepare("SELECT * FROM name_privileges");
    $stmt->execute();

    // Получаем все строки результата запроса в виде ассоциативного массива
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Начинаем формирование HTML-таблицы
    $html = '<table id="allPrivilegesTable">
        <thead>
            <tr>
                <th>ID Привилегии</th>
                <th>Имя привилегии</th>
            </tr>
        </thead>
        <tbody>';

    // Проверяем, есть ли данные в результате запроса
    if (empty($data)) {
        // Если данных нет, добавляем строку с сообщением "Нет данных"
        $html .= '<tr><td colspan="4">Нет данных</td></tr>';
    } else {
        // Если данные есть, проходим по каждой строке и добавляем её в таблицу
        foreach ($data as $privilege) {
            $html .= '<tr>
                <td>' . htmlspecialchars($privilege['id_privileges']) . '</td>
                <td>' . htmlspecialchars($privilege['name_privileges']) . '</td>
            </tr>';
        }
    }

    // Завершаем формирование HTML-таблицы
    $html .= '</tbody></table>';

    logger("ERROR", "Получены привилегии " . $html);

    // Устанавливаем заголовок Content-Type как text/html
    header('Content-Type: text/html');

    // Выводим сформированный HTML-код таблицы
    echo $html;
?>