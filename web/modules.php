<?php
require_once 'include/function.php';

// Логирование начала выполнения скрипта
logger("INFO", "Начало выполнения скрипта modules.php.");

// Запуск сессии
startSessionIfNotStarted();
logger("INFO", "Сессия успешно запущена. ID сессии: " . session_id());

// Проверка авторизации
checkAuth();
logger("INFO", "Пользователь авторизован. Username: " . $_SESSION['username']);

// Генерация CSRF-токена
csrf_token();
logger("INFO", "CSRF-токен сгенерирован.");

//Проверка на фрод
frod('f47ac10b-58cc-4372-a567-0e02b2c3d479');

// Проверка, есть ли сообщение об ошибке
$error_message = "";
if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
    logger("ERROR", "Получено сообщение об ошибке: " . $error_message);
}

// Функция для чтения и вывода меню из menu.json
function loadMenu() {
    $menuFilePath = $_SERVER['DOCUMENT_ROOT'] . '/config/menu.json';

    // Проверяем, существует ли файл
    if (!file_exists($menuFilePath)) {
        logger("ERROR", "Файл menu.json не найден по пути: " . $menuFilePath);
        return "<p>Меню недоступно. Ошибка: файл menu.json не найден.</p>";
    }

    // Считываем содержимое файла
    $menuJson = file_get_contents($menuFilePath);

    // Преобразуем JSON в ассоциативный массив
    $menuData = json_decode($menuJson, true);

    // Проверяем, удалось ли декодировать JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        logger("ERROR", "Ошибка при декодировании JSON: " . json_last_error_msg());
        return "<p>Меню недоступно. Ошибка: некорректный формат JSON.</p>";
    }

    // Генерация HTML для таблицы
    $html = '<div class="table-container">'; // Контейнер для прокрутки таблицы
    $html .= '<table>';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th>Включен</th>';
    $html .= '<th>Название</th>';
    $html .= '<th>GUID модуля</th>';
    $html .= '<th>Родительский модуль</th>';
    $html .= '<th>Страница модуля</th>';
    $html .= '<th>Иконка</th>';
    $html .= '<th>Доступные роли</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';

    // Рекурсивная функция для отображения пунктов меню
    function renderMenuItems($items, $parentTitle = '') {
        $html = '';
        foreach ($items as $item) {
            $html .= '<tr>';
            // Переключатель для включения/выключения
            $html .= '<td>
                        <label class="switch">
                            <input type="checkbox" ' . ($item['active'] ? 'checked' : '') . ' disabled>
                            <span class="slider"></span>
                        </label>
                      </td>';
            $html .= '<td><input type="text" value="' . htmlspecialchars($item['title']) . '" readonly></td>';
            $html .= '<td><input type="text" value="' . htmlspecialchars($item['guid']) . '" readonly></td>';
            $html .= '<td><input type="text" value="' . htmlspecialchars($parentTitle) . '" readonly></td>';
            $html .= '<td><input type="url" value="' . htmlspecialchars($item['url']) . '" readonly></td>';
            $html .= '<td><input type="text" value="' . htmlspecialchars($item['icon']) . '" readonly></td>';
            // Чекбоксы для ролей
            $html .= '<td>
                        <div class="role-checkbox">
                            <label><input type="checkbox" ' . (in_array('view', $item['role']) ? 'checked' : '') . ' disabled> Просмотр</label>
                            <label><input type="checkbox" ' . (in_array('edit', $item['role']) ? 'checked' : '') . ' disabled> Редактирование</label>
                        </div>
                      </td>';
            $html .= '</tr>';

            // Если есть подменю, рекурсивно отображаем его
            if (!empty($item['dropdown'])) {
                $html .= renderMenuItems($item['dropdown'], $item['title']);
            }
        }
        return $html;
    }

    $html .= renderMenuItems($menuData['menu']);
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</div>'; // Закрываем контейнер для прокрутки таблицы

    logger("INFO", "Меню успешно загружено.");
    return $html;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <?php include 'include/all_head.html'; ?>
    <!-- Подключаем файл стилей -->
    <link rel="stylesheet" href="/css/modules.css">
</head>
<body>
    <?php include 'include/header.html'; ?>
    <?php include 'include/navbar.html'; ?>
    <main>
        <!-- Кнопки управления -->
        <div class="button-container">
            <a href="#" class="button" id="editButton">Изменить</a>
            <a href="#" class="button save" id="saveButton" disabled>Сохранить</a>
            <a href="#" class="button add" id="addButton" disabled>Добавить модуль</a>
        </div>

        <!-- Вывод меню в виде таблицы -->
        <div class="menu-container">
            <?php echo loadMenu(); ?>
        </div>

        <!-- Вывод сообщения об ошибке, если есть -->
        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <p><?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>
    </main>
    <?php include 'include/footer.php'; ?>

    <!-- Подключаем скрипт -->
    <script src="/js/modules.js"></script>
</body>
</html>