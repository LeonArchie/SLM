<?php
    $modules = '356a5297-1587-4d79-8f81-b3e1c7e21a73';
    $pages = '62583be6-530e-42b2-b490-b0b082d47e66';

	$file_path = __DIR__ . '/include/platform.php';
    if (!file_exists($file_path)) {
        // Если файл не существует, переходим на страницу 503.php
        header("Location: /err/50x.html");
        exit();
    }
	require_once $file_path;
    
    $file_path = __DIR__ . '/back/load_my_account.php';
    if (!file_exists($file_path)) {
        // Если файл не существует, переходим на страницу 503.php
        header("Location: /err/50x.html");
        exit();
    }
	require_once $file_path;

    logger("DEBUG", "Фрод готов к запуску");
    FROD($modules);

    // Логирование начала выполнения скрипта
    logger("INFO", "Начало выполнения скрипта dashboard.php.");

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
    // Получаем данные пользователя при загрузке страницы
    $userData = getUserData();
    
    // Если произошла ошибка при получении данных
    if (isset($userData['error'])) {
        $error_message = $userData['error'];
    }
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <?php include ROOT_PATH . '/include/all_head.html'; ?>
    <!-- Подключение стилей -->
    <link rel="stylesheet" href="/css/error.css"/>
    <link rel="stylesheet" href="/css/navbar.css"/>
    <link rel="stylesheet" href="css/my_account.css"/>
</head>
<body>
    <?php include ROOT_PATH . '/include/eos_header.html'; ?>
    <?php include ROOT_PATH .'/include/navbar.php'; ?>
    <main>
        <div class="form-container">
            <!-- Группа кнопок (фиксированная) -->
            <div class="button-group fixed-buttons">
                <button class="form-button" id="updateButton" onclick="location.reload()">Обновить</button>
                <button class="form-button">Сохранить</button>
                <button class="form-button">Сменить пароль</button>
            </div>
            <!-- Скроллируемая форма -->
            <div class="scrollable-form">
                <!-- Секция профиля -->
                <div class="profile-section">
                    <div class="user-info">
                        <div class="form-field">
                            <label for="userID">UserID:</label>
                            <input type="text" id="userID" name="userID" readonly value="<?= htmlspecialchars($_SESSION['userid'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="form-field">
                            <label for="login">Логин:</label>
                            <input type="text" id="login" name="login" readonly value="<?= htmlspecialchars($userData['userlogin'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="form-field">
                            <label for="lastName">Фамилия:</label>
                            <input type="text" id="lastName" name="lastName" value="<?= htmlspecialchars($userData['family'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="form-field">
                            <label for="firstName">Имя:</label>
                            <input type="text" id="firstName" name="firstName" value="<?= htmlspecialchars($userData['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="form-field">
                            <label for="fullName">Полное ФИО:</label>
                            <input type="text" id="fullName" name="fullName" value="<?= htmlspecialchars($userData['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                    <div class="profile-picture">
                        <img src="img/user_icon.png" alt="Аватар">
                        <div class="active-status">
                            <label for="active">Активен:</label>
                            <input type="checkbox" id="active" name="active" disabled <?= isset($userData['active']) && $userData['active'] ? 'checked' : '' ?>>
                        </div>
                    </div>
                </div>
                <!-- Форма -->
                <form>
                    <!-- Email и Телефон (в одну строку) -->
                    <div class="form-row spaced-fields">
                        <div class="form-field">
                            <label for="email">E-mail:</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($userData['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="form-field">
                            <label for="phone">Телефон:</label>
                            <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($userData['telephone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                    <!-- Роль и RoleID (в одну строку) -->
                    <div class="form-row spaced-fields">
                        <div class="form-field">
                            <label for="role">Роль:</label>
                            <input type="text" id="role" name="role" readonly value="<?= htmlspecialchars($userData['names_rol'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="form-field">
                            <label for="roleID">RoleID:</label>
                            <input type="text" id="roleID" name="roleID" readonly value="<?= htmlspecialchars($userData['roleid'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                    <!-- LDAP секция -->
                    <div class="ldap-section">
                        <h3>LDAP</h3>
                        <div class="form-field">
                            <label for="ldapActive">Активирован:</label>
                            <input type="checkbox" id="ldapActive" name="ldapActive" disabled <?= isset($userData['LDAP']) && $userData['LDAP'] ? 'checked' : '' ?>>
                        </div>
                        <div class="form-field">
                            <label for="dn">DN пользователя:</label>
                            <input type="text" id="dn" name="dn" readonly value="<?= htmlspecialchars($userData['DN'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                    <!-- Внешние взаимодействия -->
                    <div class="external-interactions">
                        <h3>Внешние взаимодействия</h3>
                        <div class="form-field api-key-field">
                            <button class="form-button" id="getAPIKey">Получить ключ API</button>
                            <input type="text" id="apiKey" name="apiKey" readonly>
                        </div>
                        <div class="form-row">
                            <div class="form-field">
                                <label for="telegramUsername">Telegram Username:</label>
                                <input type="text" id="telegramUsername" name="telegramUsername" value="<?= htmlspecialchars($userData['tg_username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="form-field">
                                <label for="telegramID">Telegram ID:</label>
                                <input type="text" id="telegramID" name="telegramID" value="<?= htmlspecialchars($userData['tg_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <?php include ROOT_PATH . '/include/loading.html'; ?>
        </div>
        
    </main>
    <?php include ROOT_PATH . '/include/error.php'; ?>
    <?php include ROOT_PATH . '/include/footer.php'; ?>
</body>
</html>