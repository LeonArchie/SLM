<?php
    $modules = 'da137713-83fe-4325-868f-14b967dbf17c';
    $pages = '62583be6-530e-42b2-b490-b0b082d47e66';

	$file_path = __DIR__ . '/include/platform.php';
    if (!file_exists($file_path)) {
        // Если файл не существует, переходим на страницу 503.php
        header("Location: /err/50x.html");
        exit();
    }
	require_once $file_path;
    
    logger("DEBUG", "Фрод готов к запуску");
    FROD($modules);

    // Логирование начала выполнения скрипта
    logger("INFO", "Начало выполнения скрипта all_accounts.php.");

    // Проверка, есть ли сообщение об ошибке
    $error_message = "";
    if (isset($_GET['error'])) {
        $raw_error = $_GET['error']; // Сохраняем сырое значение
        $error_message = htmlspecialchars($raw_error, ENT_QUOTES, 'UTF-8');
        logger("DEBUG", "Сырое значение параметра error: " . $raw_error);
        logger("ERROR", "Получено сообщение об ошибке: " . $error_message);
    } else {
        logger("INFO", "Параметр 'error' не передан.");
    }
?>
<!DOCTYPE html>
<html lang="ru">
    <head>
        <?php include ROOT_PATH . '/include/all_head.html'; ?>
        <!-- Подключение стилей -->
        <link rel="stylesheet" href="/css/error.css"/>
        <link rel="stylesheet" href="/css/navbar.css"/>
        <link rel="stylesheet" href="css/all_accounts.css"/>
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
            <button id="syncLdapButton">Принудительная синхронизация LDAP</button>
            <button id="ldapSettingsButton">Настройки LDAP</button>
            <button id="refreshButton">Обновить</button>
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
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><input type="checkbox" class="userCheckbox" data-userid="<?= htmlspecialchars($user['userid']) ?>"></td>
                            <td class="name-cell"><a href="#" data-userid="<?= htmlspecialchars($user['userid']) ?>"><?= htmlspecialchars($user['full_name']) ?></a></td>
                            <td><?= htmlspecialchars($user['userlogin']) ?></td>
                            <td><?= htmlspecialchars($user['names_rol']) ?></td>
                            <td><input type="checkbox" disabled <?= $user['active'] ? 'checked' : '' ?>></td>
                            <td><input type="checkbox" disabled <?= $user['add_ldap'] ? 'checked' : '' ?>></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
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
            <!-- Новое поле "Роль" -->
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
        <script src="js/createuser.js"></script>
        <script src="js/delete.js"></script>
        <script src="js/block.js"></script>
    </body>
</html>