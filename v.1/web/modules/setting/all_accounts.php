<?php
    // Уникальный идентификатор страницы для проверки привилегий
    $privileges_page = 'da137713-83fe-4325-868f-14b967dbf17c';

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

    // Подключение к базе данных с обработкой исключений
    try {
        $pdo = connectToDatabase();
    } catch (PDOException $e) {
        // Логирование ошибки подключения к базе данных
        logger("ERROR", "Ошибка подключения к базе данных: " . $e->getMessage());
        // Перенаправление на страницу ошибки 500
        header("Location: /err/50x.html");
        exit();
    }

    // Подготовка SQL-запроса для получения списка пользователей
    $stmt = $pdo->prepare("SELECT userlogin, full_name, active, add_ldap, userid FROM users");

    // Выполнение запроса и проверка на ошибки
    if (!$stmt->execute()) {
        // Логирование ошибки, если запрос не выполнился
        logger("ERROR", "Ошибка получения списка пользователей.");
        // Перенаправление на страницу ошибки 500
        header("Location: /err/50x.html");
        exit();
    }

    // Получение всех записей из результата запроса
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            <link rel="stylesheet" href="css/all_accounts.css"/>
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
                        <button id="addButton">Добавить</button>
                        <button id="editButton" disabled>Редактировать</button>
                        <button id="blockButton" disabled>Заблокировать</button>
                        <button id="deleteButton" disabled>Удалить</button>
                        <button id="syncLdapButton" disabled>Принудительная синхронизация LDAP</button>
                        <button id="ldapSettingsButton" disabled>Настройки LDAP</button>
                        <button id="refreshButton" onclick="location.reload()">Обновить</button>
                        <!-- Скрытые поля для CSRF-токена и ID пользователя -->
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="userid" value="<?php echo $_SESSION['userid']; ?>">
                    </div>
                    <!-- Контейнер для таблицы пользователей -->
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <!-- Чекбокс для выбора всех пользователей -->
                                    <th><input type="checkbox" id="selectAll"></th>
                                    <th>Полное ФИО</th>
                                    <th>Логин</th>
                                    <th>Активен</th>
                                    <th>LDAP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    // Преобразование массива пользователей в индексированный массив
                                    $users = array_values($users);
                                    // Цикл для отображения каждого пользователя в таблице
                                    foreach ($users as $index => $user):
                                        // Экранирование данных пользователя для безопасного отображения
                                        $fullName = htmlspecialchars($user['full_name'] ?? 'Без имени');
                                        $userLogin = htmlspecialchars($user['userlogin'] ?? 'Без логина');
                                        $isActive = !empty($user['active']) ? 'checked' : '';
                                        $isLdap = !empty($user['add_ldap']) ? 'checked' : '';
                                ?>
                                    <tr>
                                        <!-- Чекбокс для выбора конкретного пользователя -->
                                        <td>
                                            <input type="checkbox" class="userCheckbox" data-userid="<?= htmlspecialchars($user['userid']) ?>">
                                        </td>
                                        <!-- Ссылка на редактирование пользователя -->
                                        <td class="name-cell">
                                            <a href="#" onclick="event.preventDefault(); redirectToEditUser(<?= json_encode($user['userid']) ?>);">
                                                <?= $fullName ?>
                                            </a>
                                        </td>
                                        <!-- Логин пользователя -->
                                        <td><?= $userLogin ?></td>
                                        <!-- Чекбокс для отображения активности пользователя -->
                                        <td>
                                            <input type="checkbox" disabled <?= $isActive ?> class="custom-checkbox status-indicator">
                                        </td>
                                        <!-- Чекбокс для отображения LDAP-статуса пользователя -->
                                        <td>
                                            <input type="checkbox" disabled <?= $isLdap ?> class="custom-checkbox ldap-indicator">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Форма добавления нового пользователя -->
                <div class="add-form-overlay" id="addFormOverlay">
                    <div class="add-form">
                        <form id="addUserForm">
                            <!-- Скрытое поле для CSRF-токена -->
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <!-- Поле для ввода полного имени -->
                            <div class="input-group">
                                <label for="full_name">Полное ФИО:</label>
                                <input type="text" id="full_name" name="full_name" required>
                            </div>
                            <!-- Поле для ввода логина -->
                            <div class="input-group">
                                <label for="userlogin">Логин:</label>
                                <input type="text" id="userlogin" name="userlogin" required>
                            </div>
                            <!-- Поле для ввода пароля с кнопкой генерации пароля -->
                            <div class="input-group password-container">
                                <label for="password">Пароль:</label>
                                <input type="text" id="password" name="password" required>
                                <button type="button" id="generate-password">Сгенерировать</button>
                            </div>
                            <!-- Поле для ввода email -->
                            <div class="input-group">
                                <label for="email">E-mail:</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                            <!-- Выпадающий список для выбора роли пользователя -->
                            <div class="input-group">
                                <label for="role">Полномочия:</label>
                                <select id="role" name="role" required <?php echo (isset($error) ? 'disabled' : ''); ?>>
                                    <?php
                                    // Путь к файлу с шаблонами привилегий
                                    $templatePath = TEMPLATE_PRIVILEGES;

                                    // Проверка существования файла
                                    if (!file_exists($templatePath)) {
                                        // Логирование ошибки, если файл не найден
                                        logger("ERROR", "Ошибка получения шаблонов полномочий - нет файла");
                                        $error = true; // Установка флага ошибки
                                        echo '<option value="default" selected>По умолчанию</option>';
                                    } else {
                                        // Чтение JSON-файла
                                        $jsonData = file_get_contents($templatePath);
                                        $privilegesData = json_decode($jsonData, true);

                                        // Проверка на ошибки декодирования JSON
                                        if (json_last_error() !== JSON_ERROR_NONE || !is_array($privilegesData)) {
                                            // Логирование ошибки, если JSON не удалось декодировать
                                            logger("ERROR", "Ошибка получения шаблонов полномочий - не могу декодировать");
                                            $error = true; // Установка флага ошибки
                                            echo '<option value="default" selected>По умолчанию</option>';
                                        } else {
                                            // Генерация опций для выбора роли
                                            echo '<option value="" disabled selected>Выберите полномочия</option>';
                                            foreach ($privilegesData as $key => $privilege) {
                                                $name = htmlspecialchars($privilege['name']);
                                                echo '<option value="' . $key . '">' . $name . '</option>';
                                            }
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <!-- Кнопки для отмены и создания пользователя -->
                            <div class="button-group">
                                <button type="button" class="cancel">Отменить</button>
                                <button type="submit" class="create">Создать</button>
                            </div>
                        </form>
                    </div>
                </div>

            </main>
            <!-- Подключение блока для отображения ошибок -->
            <?php include ROOT_PATH . '/include/error.php'; ?>
            <!-- Подключение футера -->
            <?php include ROOT_PATH . '/include/footer.php'; ?>
            <!-- Подключение JavaScript-файлов -->
            <script src="js/all_account/all_accounts.js"></script>
            <script src="js/all_account/button_edit_all_acc.js"></script>
            <script src="js/all_account/createuser.js"></script>
            <script src="js/all_account/all_acc_delete.js"></script>
            <script src="js/all_account/all_acc_block.js"></script>
            <script src="/js/error.js"></script>
        </body>
    </html>