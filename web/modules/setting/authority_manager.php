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
                    <button id="AssignPrivileges">Назначить полномочия</button>
                    <button id="VievPrivileges" disabled>Просмотреть полномочия</button>
                    <button id="DeletePrivileges" disabled>Снять полномочия</button>
                    <button id="CreatePrivileges" disabled>Создать полномочия</button>
                    <button id="DeletePrivileges">Удалить полномочия</button>
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
        </main>
        <?php include ROOT_PATH . '/include/error.php'; ?>
        <?php include ROOT_PATH . '/include/footer.php'; ?>
    </body>
</html>