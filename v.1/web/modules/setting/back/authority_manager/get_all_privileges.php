<?php
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
    }
    $file_path = ROOT_PATH . '/include/function.php';

    // Проверяем существование файла function.php
    if (!file_exists($file_path)) {
        echo json_encode(['success' => false, 'message' => 'Ошибка сервера: файл function.php не найден.']);
        exit();
    }

    require_once $file_path;

    startSessionIfNotStarted();

    // Запрос к таблице name_privileges
    $pdo = connectToDatabase();
    $stmt = $pdo->prepare("SELECT * FROM name_privileges");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Генерация HTML-таблицы
    $html = '<table id="allPrivilegesTable">
        <thead>
            <tr>
                <th>ID Привилегии</th>
                <th>Имя привилегии</th>
            </tr>
        </thead>
        <tbody>';

    if (empty($data)) {
        $html .= '<tr><td colspan="4">Нет данных</td></tr>';
    } else {
        foreach ($data as $privilege) {
            $html .= '<tr>
                <td>' . htmlspecialchars($privilege['id_privileges']) . '</td>
                <td>' . htmlspecialchars($privilege['name_privileges']) . '</td>
            </tr>';
        }
    }

    $html .= '</tbody></table>';

    // Возвращаем HTML
    header('Content-Type: text/html');
    echo $html;

?>