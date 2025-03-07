    <?php

        $modules = 'aeef82b7-5083-480e-a59e-507a083a16be';
        $pages = '279a4365-7161-438d-b95a-fc1e017eb763';

        $file_path = __DIR__ . '/include/platform.php';
        if (!file_exists($file_path)) {
            header("Location: /err/50x.html");
            exit();
        }
        require_once $file_path;

        FROD($modules);

        // Логирование начала выполнения скрипта
        logger("INFO", "Начало выполнения скрипта authority_manager.php.");

        // Подключение к базе данных
        $pdo = connectToDatabase();
        logger("INFO", "Успешное подключение к базе данных.");

        // Запрос к таблице users для получения всех пользователей
        $stmt = $pdo->prepare("SELECT userlogin, full_name, active, userid FROM users");
        logger("DEBUG", "Выполняется запрос к таблице users: SELECT userlogin, full_name, active, userid FROM users");

        if (!$stmt->execute()) {
            logger("ERROR", "Ошибка при выполнении запроса к таблице users.");
            echo "Ошибка при загрузке данных.";
            exit();
        }

        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        logger("DEBUG", "Получено " . count($users) . " записей из таблицы users.");
        logger("DEBUG", "Получены данные: " . print_r($users, true));

        // Запрос к таблице name_privileges для получения всех привилегий
        $stmt = $pdo->prepare("SELECT * FROM name_privileges");
        $stmt->execute();
        $name_privileges = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Проверка, есть ли сообщение об ошибке
        $error_message = "";
        if (isset($_GET['error'])) {
            $raw_error = $_GET['error']; // Сохраняем сырое значение
            $error_message = htmlspecialchars($raw_error, ENT_QUOTES, 'UTF-8');
            logger("DEBUG", "Сырое значение параметра error: " . $raw_error);
            logger("ERROR", "Получено сообщение об ошибке: " . $error_message);
        }
    ?>
    <!DOCTYPE html>
    <html lang="ru">
        <head>
            <?php include ROOT_PATH . '/include/all_head.html'; ?>
            <link rel="stylesheet" href="/css/navbar.css"/>
            <link rel="stylesheet" href="css/authority_manager.css"/>
            <link rel="stylesheet" href="/css/error.css"/>
        </head>
        <body>
            <?php include ROOT_PATH . '/include/eos_header.html'; ?>
            <?php include ROOT_PATH .'/include/navbar.php'; ?>
            <main>
                <?php if (!empty($error_message)): ?>
                    <div class="error-message">
                        <?= $error_message ?>
                    </div>
                <?php endif; ?>

                <div class="form-container">
                    <div class="button-bar">
                        <button id="AssignPrivileges" disabled>Назначить полномочия</button>
                        <button id="VievPrivileges" disabled>Просмотреть полномочия</button>
                        <button id="OffPrivileges" disabled>Снять полномочия</button>
                        <button id="CreatePrivileges">Создать полномочия</button>
                        <button id="ViewAllPrivileges">Просмотреть все полномочия</button>
                        <button id="DeletePrivileges">Удалить полномочия</button>
                        <button id="refreshButton" onclick="location.reload()">Обновить</button>
                        <input type="hidden" name="userid" value="<?php echo $_SESSION['userid']; ?>">
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAll"></th>
                                    <th>Полное ФИО</th>
                                    <th>Логин</th>
                                    <th>ID пользователя</th>
                                    <th>Активен</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $index => $user): ?>
                                    <?php
                                    $fullName = htmlspecialchars($user['full_name'] ?? 'Без имени');
                                    $userLogin = htmlspecialchars($user['userlogin'] ?? 'Без логина');
                                    $userId = htmlspecialchars($user['userid'] ?? 'Без ID');
                                    $isActive = !empty($user['active']) ? 'checked' : '';
                                    ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="userCheckbox" data-userid="<?= $userId ?>">
                                        </td>
                                        <td class="name-cell">
                                            <a href="#" onclick="event.preventDefault(); redirectToEditUser(<?= json_encode($userId) ?>);">
                                                <?= $fullName ?>
                                            </a>
                                        </td>
                                        <td><?= $userLogin ?></td>
                                        <td><?= $userId ?></td>
                                        <td>
                                            <input type="checkbox" disabled <?= $isActive ?> class="custom-checkbox status-indicator">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <!-- Форма для создания полномочий -->
                <div id="createPrivilegesForm" class="form-modal" style="display: none;">
                    <h2>Создать полномочия</h2>
                    <form id="createPrivilegesFormContent">
                        <div class="input-group">
                            <label for="privilegeName">Имя:</label>
                            <input type="text" id="privilegeName" name="privilegeName" required>
                        </div>
                        <div class="input-group">
                            <label for="privilegeID">ID:</label>
                            <input type="text" id="privilegeID" name="privilegeID" required>
                        </div>
                        <div class="input-group">
                            <label for="pagesCheckbox">Pages:</label>
                            <input type="checkbox" id="pagesCheckbox" name="pagesCheckbox">
                        </div>
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="button-group">
                            <button type="button" id="submitCreatePrivilegesForm">Создать</button>
                            <button type="button" id="cancelCreatePrivilegesForm">Отменить</button>
                        </div>
                    </form>
                </div>

                <!-- Форма для удаления полномочий -->
                <div id="deletePrivilegesForm" class="form-modal" style="display: none;">
                    <h2>Удалить полномочия</h2>
                    <form id="deletePrivilegesFormContent">
                        <div class="input-group select-group">
                            <label for="privilegesToDelete">Привилегии:</label>
                            <select id="privilegesToDelete" name="privilegesToDelete[]" multiple>
                                <?php foreach ($name_privileges as $privilege): ?>
                                    <option value="<?= $privilege['id_privileges'] ?>"><?= $privilege['name_privileges'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="button-group">
                            <button type="button" id="submitDeletePrivilegesForm">Удалить</button>
                            <button type="button" id="cancelDeletePrivilegesForm">Отменить</button>
                        </div>
                    </form>
                </div>

                <!-- Форма для снятия полномочий -->
                <div id="revokePrivilegesForm" class="form-modal" style="display: none;">
                    <h2>Снять полномочия</h2>
                    <form id="revokePrivilegesFormContent">
                        <div class="input-group">
                            <label for="userIDRevoke">UserID:</label>
                            <input type="text" id="userIDRevoke" name="userIDRevoke" readonly>
                        </div>
                        <div class="input-group select-group">
                            <label for="privilegesToRevoke">Привилегии:</label>
                            <select id="privilegesToRevoke" name="privilegesToRevoke[]" multiple>
                                <?php foreach ($name_privileges as $privilege): ?>
                                    <option value="<?= $privilege['id_privileges'] ?>"><?= $privilege['name_privileges'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="button-group">
                            <button type="button" id="submitRevokePrivilegesForm">Снять</button>
                            <button type="button" id="cancelRevokePrivilegesForm">Отменить</button>
                        </div>
                    </form>
                </div>

                <!-- Форма для просмотра полномочий -->
                <div id="viewPrivilegesForm" class="form-modal" style="display: none;">
                    <h2>Просмотр полномочий</h2>
                    <form id="viewPrivilegesFormContent">
                        <div class="input-group">
                            <label for="userIDView">UserID:</label>
                            <input type="text" id="userIDView" name="userIDView" readonly>
                        </div>
                        <div id="privilegesTableContainer"></div> <!-- Контейнер для таблицы -->
                        <div class="button-group">
                            <button type="button" id="closeViewPrivilegesForm">Закрыть</button>
                        </div>
                    </form>
                </div>

                <!-- Форма для просмотра всех полномочий -->
                <div id="viewAllPrivilegesForm" class="form-modal" style="display: none;">
                    <h2>Все полномочия</h2>
                    <form id="viewAllPrivilegesFormContent">
                        <!-- Контейнер для таблицы -->
                        <div id="tableContainer"></div>
                    </form>
                    <div class="button-group">
                        <button type="button" id="closeViewAllPrivilegesForm">Закрыть</button>
                    </div>
                </div>

                <!-- Форма для назначения полномочий -->
                <div id="assignPrivilegesForm" class="form-modal" style="display: none;">
                    <h2>Назначить полномочия</h2>
                    <form id="assignPrivilegesFormContent">
                        <!-- Поле UserID -->
                        <div class="input-group">
                            <label for="userIDAssign">UserID:</label>
                            <input type="text" id="userIDAssign" name="userIDAssign" readonly>
                        </div>

                        <!-- Поле с множественным выбором для полномочий -->
                        <div class="input-group select-group">
                            <label for="privilegesToAssign">Полномочия:</label>
                            <select id="privilegesToAssign" name="privilegesToAssign[]" multiple>
                                <?php foreach ($name_privileges as $privilege): ?>
                                    <option value="<?= $privilege['id_privileges'] ?>"><?= $privilege['name_privileges'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- Скрытое поле для CSRF-токена -->
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <!-- Кнопки -->
                        <div class="button-group">
                            <button type="button" id="submitAssignPrivilegesForm">Назначить полномочия</button>
                            <button type="button" id="cancelAssignPrivilegesForm">Отменить</button>
                        </div>
                    </form>
</div>
            </main>
            <?php include ROOT_PATH . '/include/error.php'; ?>
            <?php include ROOT_PATH . '/include/footer.php'; ?>

            <script src="js/authority_manager/auth_manager.js"></script>
            <script src="js/authority_manager/create_privileges.js"></script>
            <script src="js/authority_manager/delete_privileges.js"></script>
            <script src="js/authority_manager/revoke_privileges.js"></script>
            <script src="js/authority_manager/view_privileges.js"></script>
            <script src="js/authority_manager/view_all_privileges.js"></script>
            <script src="js/authority_manager/assign_privileges.js"></script>
            <script src="/js/error.js"></script>
        </body>
    </html>