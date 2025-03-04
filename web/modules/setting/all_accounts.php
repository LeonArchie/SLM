<?php
    $modules = 'da137713-83fe-4325-868f-14b967dbf17c';
    $pages = '34868747-e94d-4294-9fb5-20aaf163ba7f';

    $file_path = __DIR__ . '/include/platform.php';
    if (!file_exists($file_path)) {
        // Если файл не существует, переходим на страницу 503.php
        header("Location: /err/50x.html");
        exit();
    }
    require_once $file_path;
    
    //logger("DEBUG", "Фрод готов к запуску");
    FROD($modules);

    // Логирование начала выполнения скрипта
    //logger("INFO", "Начало выполнения скрипта all_accounts.php.");

    // Подключение к базе данных
    $pdo = connectToDatabase();
    //logger("INFO", "Успешное подключение к базе данных.");

    // Запрос к таблице users для получения пользователей
    $stmt = $pdo->prepare("SELECT userlogin, full_name, active, add_ldap, userid FROM users");
    //logger("DEBUG", "Выполняется запрос к таблице users: SELECT userlogin, full_name, active, add_ldap, userid FROM users");

    if (!$stmt->execute()) {
        logger("ERROR", "Ошибка при выполнении запроса к таблице users.");
        echo "Ошибка при загрузке данных.";
        exit();
    }

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //logger("DEBUG", "Получено " . count($users) . " записей из таблицы users.");
    //logger("DEBUG", "Получены данные: " . print_r($users, true));

    // Проверка, есть ли сообщение об ошибке
    $error_message = "";
    if (isset($_GET['error'])) {
        $raw_error = $_GET['error']; // Сохраняем сырое значение
        $error_message = htmlspecialchars($raw_error, ENT_QUOTES, 'UTF-8');
        logger("DEBUG", "Сырое значение параметра error: " . $raw_error);
        logger("ERROR", "Получено сообщение об ошибке: " . $error_message);
    } else {
        //logger("INFO", "Параметр 'error' не передан.");
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
                <?php
                // Логируем начало формирования таблицы
                //logger("INFO", "=== НАЧАЛО ФОРМИРОВАНИЯ ТАБЛИЦЫ ПОЛЬЗОВАТЕЛЕЙ ===");

                // Логируем количество пользователей
                //logger("DEBUG", "Количество пользователей для отображения: " . count($users));
                ?>

                <div class="form-container">
                    <div class="button-bar">
                        <button id="addButton">Добавить</button>
                        <button id="editButton" disabled>Редактировать</button>
                        <button id="blockButton" disabled>Заблокировать</button>
                        <button id="deleteButton" disabled>Удалить</button>
                        <button id="syncLdapButton">Принудительная синхронизация LDAP</button>
                        <button id="ldapSettingsButton">Настройки LDAP</button>
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
                                    <th>Роль</th>
                                    <th>Активен</th>
                                    <th>LDAP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Логируем структуру данных пользователей
                                //logger("DEBUG", "Данные пользователей: " . print_r($users, true));
                                $users = array_values($users);
                                //logger("DEBUG", "Данные пользователей: " . print_r($users, true));
                                foreach ($users as $index => $user):
                                    
                                    // Логируем начало обработки строки таблицы
                                    //logger("INFO", "--- Обработка строки таблицы для пользователя #" . ($index + 1) . " ---");

                                    // Логируем данные текущего пользователя
                                    //logger("DEBUG", "Данные пользователя #" . ($index + 1) . ": " . print_r($user, true));

                                    // Логируем проверку наличия данных
                                    $fullName = htmlspecialchars($user['full_name'] ?? 'Без имени');
                                    $userLogin = htmlspecialchars($user['userlogin'] ?? 'Без логина');
                                    $isActive = !empty($user['active']) ? 'checked' : '';
                                    $isLdap = !empty($user['add_ldap']) ? 'checked' : '';

                                    //logger("DEBUG", "Полное ФИО: " . $fullName);
                                    //logger("DEBUG", "Логин: " . $userLogin);
                                    //logger("DEBUG", "Активен: " . ($isActive ? 'Да' : 'Нет'));
                                    //logger("DEBUG", "LDAP: " . ($isLdap ? 'Да' : 'Нет'));
                                    ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="userCheckbox" data-userid="<?= htmlspecialchars($user['userid']) ?>">
                                            <?php //logger("DEBUG", "Добавлен чекбокс для пользователя #" . ($index + 1) . " с userid: " . $user['userid']); ?>
                                        </td>
                                        <td class="name-cell">
                                            <a href="#" onclick="event.preventDefault(); redirectToEditUser(<?= json_encode($user['userid']) ?>);">
                                                <?= $fullName ?>
                                            </a>
                                            <?php //logger("DEBUG", "Добавлена ссылка на редактирование для пользователя #" . ($index + 1) . " с userid: " . $user['userid']); ?>
                                        </td>
                                        <td><?= $userLogin ?></td>
                                        <td>Не указано</td> <!-- Оставляем поле для роли, но не заполняем его -->
                                        <td>
                                            <input type="checkbox" disabled <?= $isActive ?> class="custom-checkbox status-indicator">
                                            <?php //logger("DEBUG", "Добавлен чекбокс активности для пользователя #" . ($index + 1)); ?>
                                        </td>
                                        <td>
                                            <input type="checkbox" disabled <?= $isLdap ?> class="custom-checkbox ldap-indicator">
                                            <?php //logger("DEBUG", "Добавлен чекбокс LDAP для пользователя #" . ($index + 1)); ?>
                                        </td>
                                    </tr>
                                    <?php
                                    // Логируем завершение обработки строки таблицы
                                    //logger("INFO", "--- Завершение обработки строки таблицы для пользователя #" . ($index + 1) . " ---");
                                    ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php
                // Логируем завершение формирования таблицы
                //logger("INFO", "=== ЗАВЕРШЕНИЕ ФОРМИРОВАНИЯ ТАБЛИЦЫ ПОЛЬЗОВАТЕЛЕЙ ===");
                ?>

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
                            <!-- Возвращаем поле "Роль" в форму добавления -->
                            <div class="input-group">
                                <label for="role">Роль:</label>
                                <select id="role" name="role" required>
                                    <option value="" disabled selected>Выберите роль</option>
                                    <?php
                                    // Подключение к базе данных
                                    $pdo = connectToDatabase();

                                    // Получение списка ролей из таблицы name_rol
                                    $stmt = $pdo->prepare("SELECT names_rol FROM name_rol");
                                    $stmt->execute();
                                    $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

                                    // Генерация <option> для каждой роли
                                    foreach ($roles as $role) {
                                        echo '<option value="' . htmlspecialchars($role) . '">' . htmlspecialchars($role) . '</option>';
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
            <script src="js/all_accounts.js"></script>
            <script src="js/button_edit_all_acc.js"></script>
            <script src="js/createuser.js"></script>
            <script src="js/all_acc_delete.js"></script>
            <script src="js/all_acc_block.js"></script>
        </body>
    </html>