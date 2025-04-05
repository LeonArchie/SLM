<?php
    // Уникальный идентификатор страницы для проверки привилегий
    $privileges_page = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';

    // Путь к файлу platform.php
    $file_path = __DIR__ . '/include/platform.php';
    // Проверка существования файла platform.php
    if (!file_exists($file_path)) {
        // Если файл не существует, перенаправляем на страницу ошибки 500
        header("Location: /err/50x.html");
        exit();
    }
    // Подключение файла platform.php
    require_once $file_path;

    // Инициализация сессии, если она еще не начата
    startSessionIfNotStarted();
    // Проверка авторизации пользователя
    checkAuth();
    // Генерация CSRF-токена для защиты от атак
    csrf_token();

    // Проверка привилегий для доступа к странице
    FROD($privileges_page);

    // Инициализация переменной для сообщения об ошибке
    $error_message = "";
    // Проверка наличия параметра ошибки в URL
    if (isset($_GET['error'])) {
        // Получение и экранирование значения ошибки
        $raw_error = $_GET['error'];
        $error_message = htmlspecialchars($raw_error, ENT_QUOTES, 'UTF-8');
    }
?>
<!DOCTYPE html>
    <html lang="ru">
        <head>
            <!-- Подключение общих мета-тегов и стилей -->
            <?php include ROOT_PATH . '/include/all_head.html'; ?>
            <!-- Подключение дополнительных стилей -->
            <link rel="stylesheet" href="/css/navbar.css"/>
            <link rel="stylesheet" href="css/modules.css"/>
            <link rel="stylesheet" href="/css/error.css"/>
        </head>
        <body>
            <!-- Подключение шапки сайта -->
            <?php include ROOT_PATH . '/include/eos_header.html'; ?>
            <!-- Подключение навигационной панели -->
            <?php include ROOT_PATH .'/include/navbar.php'; ?>
            <main>
                <!-- Контейнер для формы и таблицы -->
                <div class="form-container">
                    <!-- Панель кнопок для управления пользователями -->
                    <div class="button-bar">
                        <button id="EditButton">Радактировать</button>
                        <button id="SaveButton" disabled>Сохранить</button>
                        <button id="EnablaDisableButton" disabled>Включить/Отключить модуль</button>
                        <button id="refreshButton" onclick="location.reload()">Обновить</button>
                        <!-- Скрытые поля для CSRF-токена и ID пользователя -->
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="userid" value="<?php echo $_SESSION['userid']; ?>">
                    </div>
                    <!-- Контейнер для таблицы пользователей -->
                    <div class="table-container">
                        <table id="modulesTable">
                            <thead>
                                <tr>
                                    <th>GUID</th>
                                    <th>Название</th>
                                    <th>URL</th>
                                    <th>Иконка</th>
                                    <th>Активен</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Чтение и декодирование JSON файла
                                $modulesJson = file_get_contents(ROOT_PATH . '/config/modules.json');
                                $modulesData = json_decode($modulesJson, true);
                                
                                // Функция для рекурсивного вывода модулей
                                function renderModules($modules, $level = 0) {
                                    foreach ($modules as $module) {
                                        $indent = str_repeat('&nbsp;&nbsp;&nbsp;', $level);
                                        ?>
                                        <tr data-guid="<?= $module['guid'] ?>" data-level="<?= $level ?>">
                                            <td><span class="guid"><?= htmlspecialchars($module['guid']) ?></span></td>
                                            <td>
                                                <?= $indent ?>
                                                <span class="editable" data-field="title"><?= htmlspecialchars($module['title']) ?></span>
                                            </td>
                                            <td><span class="editable" data-field="url"><?= htmlspecialchars($module['url']) ?></span></td>
                                            <td><span class="editable" data-field="icon"><?= htmlspecialchars($module['icon']) ?></span></td>
                                            <td><input type="checkbox" class="active-checkbox" <?= $module['active'] ? 'checked' : '' ?> disabled></td>
                                        </tr>
                                        <?php
                                        // Рекурсивный вывод подмодулей
                                        if (!empty($module['dropdown'])) {
                                            renderModules($module['dropdown'], $level + 1);
                                        }
                                    }
                                }
                                
                                renderModules($modulesData['menu']);
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
            <!-- Подключение блока для отображения ошибок -->
            <?php include ROOT_PATH . '/include/error.php'; ?>
            <!-- Подключение футера -->
            <?php include ROOT_PATH . '/include/footer.php'; ?>
            <!-- Подключение JavaScript-файлов -->
            <script src="/js/error.js"></script>
            <script src="js/modules/modules.js"></script>
            <script src="js/modules/enabledisable.js"></script>
        </body>
    </html>