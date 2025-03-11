<?php
    $privileges_page = 'da137713-83fe-4325-868f-14b967dbf17c';

    $file_path = __DIR__ . '/include/platform.php';
    if (!file_exists($file_path)) {
        header("Location: /err/50x.html");
        exit();
    }
    require_once $file_path;

    //Инициализация проверки или запуска сессии
    startSessionIfNotStarted();
    // Проверка авторизации
    checkAuth();
    // Генерация CSRF-токена
    csrf_token();

    FROD($privileges_page);

    $pdo = connectToDatabase();

    // Запрос к users для получения пользователей
    $stmt = $pdo->prepare("SELECT userlogin, full_name, active, add_ldap, userid FROM users");

    if (!$stmt->execute()) {
        logger("ERROR", "Ошибка получения списка пользователей.");
        header("Location: /err/50x.html");
        exit();
    }

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $error_message = "";
    if (isset($_GET['error'])) {
        $raw_error = $_GET['error'];
        $error_message = htmlspecialchars($raw_error, ENT_QUOTES, 'UTF-8');
    }
?>
<!DOCTYPE html>
    <html lang="ru">
        <head>
            <?php include ROOT_PATH . '/include/all_head.html'; ?>
            <!-- Подключение стилей -->
            <link rel="stylesheet" href="/css/navbar.css"/>
            <link rel="stylesheet" href="css/all_accounts.css"/>
            <link rel="stylesheet" href="/css/error.css"/>
        </head>
        <body>
            <?php include ROOT_PATH . '/include/eos_header.html'; ?>
            <?php include ROOT_PATH .'/include/navbar.php'; ?>
            <main>
                <div class="form-container">
                    <div class="button-bar">
                        <button id="addButton">Добавить</button>
                        <button id="editButton" disabled>Редактировать</button>
                        <button id="blockButton" disabled>Заблокировать</button>
                        <button id="deleteButton" disabled>Удалить</button>
                        <button id="syncLdapButton" disabled>Принудительная синхронизация LDAP</button>
                        <button id="ldapSettingsButton" disabled>Настройки LDAP</button>
                        <button id="refreshButton" onclick="location.reload()">Обновить</button>
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="userid" value="<?php echo $_SESSION['userid']; ?>">
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAll"></th>
                                    <th>Полное ФИО</th>
                                    <th>Логин</th>
                                    <th>Активен</th>
                                    <th>LDAP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $users = array_values($users);
                                    foreach ($users as $index => $user):
                                        // Логируем проверку наличия данных
                                        $fullName = htmlspecialchars($user['full_name'] ?? 'Без имени');
                                        $userLogin = htmlspecialchars($user['userlogin'] ?? 'Без логина');
                                        $isActive = !empty($user['active']) ? 'checked' : '';
                                        $isLdap = !empty($user['add_ldap']) ? 'checked' : '';
                                ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="userCheckbox" data-userid="<?= htmlspecialchars($user['userid']) ?>">
                                        </td>
                                        <td class="name-cell">
                                            <a href="#" onclick="event.preventDefault(); redirectToEditUser(<?= json_encode($user['userid']) ?>);">
                                                <?= $fullName ?>
                                            </a>
                                        </td>
                                        <td><?= $userLogin ?></td>
                                        <td>
                                            <input type="checkbox" disabled <?= $isActive ?> class="custom-checkbox status-indicator">
                                        </td>
                                        <td>
                                            <input type="checkbox" disabled <?= $isLdap ?> class="custom-checkbox ldap-indicator">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Форма добавления -->
                <div class="add-form-overlay" id="addFormOverlay">
                    <div class="add-form">
                        <form id="addUserForm">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <div class="input-group">
                                <label for="full_name">Полное ФИО:</label>
                                <input type="text" id="full_name" name="full_name" required>
                            </div>
                            <div class="input-group">
                                <label for="userlogin">Логин:</label>
                                <input type="text" id="userlogin" name="userlogin" required>
                            </div>
                            <div class="input-group password-container">
                                <label for="password">Пароль:</label>
                                <input type="text" id="password" name="password" required>
                                <button type="button" id="generate-password">Сгенерировать</button>
                            </div>
                            <div class="input-group">
                                <label for="email">E-mail:</label>
                                <input type="email" id="email" name="email" required>
                            </div>

                            <div class="input-group">
                                <label for="role">Полномочия:</label>
                                <select id="role" name="role" required <?php echo (isset($error) ? 'disabled' : ''); ?>>
                                    <?php
                                    // Путь к файлу template.json
                                    $templatePath = TEMPLATE_PRIVILEGES;

                                    // Проверяем, существует ли файл
                                    if (!file_exists($templatePath)) {
                                        logger("ERROR", "Ошибка получения шаблонов полномочий - нет файла");
                                        $error = true; // Устанавливаем флаг ошибки
                                        echo '<option value="default" selected>По умолчанию</option>';
                                    } else {
                                        // Чтение JSON-файла
                                        $jsonData = file_get_contents($templatePath);
                                        $privilegesData = json_decode($jsonData, true);

                                        // Проверяем, удалось ли декодировать JSON
                                        if (json_last_error() !== JSON_ERROR_NONE || !is_array($privilegesData)) {
                                            logger("ERROR", "Ошибка получения шаблонов полномочий - не могу декодировать");
                                            $error = true; // Устанавливаем флаг ошибки
                                            echo '<option value="default" selected>По умолчанию</option>';
                                        } else {
                                            // Генерация <option> для каждого значения name
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
                            <div class="button-group">
                                <button type="button" class="cancel">Отменить</button>
                                <button type="submit" class="create">Создать</button>
                            </div>
                        </form>
                    </div>
                </div>

            </main>
            <?php include ROOT_PATH . '/include/error.php'; ?>
            <?php include ROOT_PATH . '/include/footer.php'; ?>
            <script src="js/all_account/all_accounts.js"></script>
            <script src="js/all_account/button_edit_all_acc.js"></script>
            <script src="js/all_account/createuser.js"></script>
            <script src="js/all_account/all_acc_delete.js"></script>
            <script src="js/all_account/all_acc_block.js"></script>
            <script src="/js/error.js"></script>
        </body>
    </html>