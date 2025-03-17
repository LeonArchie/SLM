<?php
    // Уникальный идентификатор страницы для проверки привилегий
    $privileges_page = '356a5297-1587-4d79-8f81-b3e1c7e21a73';

    // Путь к файлу platform.php
    $file_path = __DIR__ . '/include/platform.php';
    // Проверяем, существует ли файл platform.php
    if (!file_exists($file_path)) {
        // Если файл не существует, перенаправляем на страницу ошибки 50x
        header("Location: /err/50x.html");
        exit();
    }
    // Подключаем файл platform.php
    require_once $file_path;

    // Путь к файлу load_account.php
    $file_path = __DIR__ . '/back/edituser/load_account.php';
    // Проверяем, существует ли файл load_account.php
    if (!file_exists($file_path)) {
        // Если файл не существует, перенаправляем на страницу ошибки 50x
        header("Location: /err/50x.html");
        exit();
    }
    // Подключаем файл load_account.php
    require_once $file_path;

    // Инициализация сессии, если она еще не начата
    startSessionIfNotStarted();
    // Проверка авторизации пользователя
    checkAuth();
    // Генерация CSRF-токена для защиты от атак
    csrf_token();

    // Проверка привилегий пользователя для доступа к странице
    FROD($privileges_page);

    // Проверяем, был ли отправлен POST-запрос и есть ли в нем параметр userid
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['userid'])) {
        // Если нет, перенаправляем на страницу all_accounts.php
        header("Location: all_accounts.php");
        exit();
    }

    // Инициализация переменной для хранения сообщения об ошибке
    $error_message = "";
    // Проверяем, есть ли в GET-запросе параметр error
    if (isset($_GET['error'])) {
        // Сохраняем сырое значение ошибки
        $raw_error = $_GET['error'];
        // Экранируем значение ошибки для безопасного вывода на страницу
        $error_message = htmlspecialchars($raw_error, ENT_QUOTES, 'UTF-8');
    }
    
    // Получаем идентификатор пользователя из POST-запроса
    $userid = $_POST['userid'];

    try {
        // Получаем данные пользователя по его идентификатору
        $userData = getUserData($userid);
    } catch (Exception $e) {
        // Логируем ошибку, если возникла проблема при получении данных
        logger("ERROR", "Ошибка при получении данных о пользователе:". $e->getMessage());
        audit("ERROR", "Ошибка при получении данных о пользователе:". $e->getMessage());
        // Перенаправляем на страницу ошибки 50x
        header("Location: /err/50x.html");
    }
    
    // Проверяем, есть ли ошибка в данных пользователя
    if (isset($userData['error'])) {
        // Если есть, сохраняем сообщение об ошибке
        $error_message = $userData['error'];
    }
?>
<!DOCTYPE html>
<html lang="ru">
    <head>
        <?php include ROOT_PATH . '/include/all_head.html'; ?>
        <!-- Подключение стилей -->
        <link rel="stylesheet" href="/css/navbar.css"/>
        <link rel="stylesheet" href="css/edituser.css"/>
        <link rel="stylesheet" href="/css/error.css"/>
    </head>
    <body>
        <?php include ROOT_PATH . '/include/eos_header.html'; ?>
        <?php include ROOT_PATH .'/include/navbar.php'; ?>
        <main>
            <div class="form-container">
                <!-- Группа кнопок (фиксированная) -->
                <div class="button-group fixed-buttons">
                    <button class="form-button" id="backButton" onclick="window.location.href='/modules/setting/all_accounts.php'">Назад</button>
                    <button class="form-button" id="updateButton" onclick="location.reload()">Сбросить</button>
                    <button class="form-button" id="saveButton">Сохранить</button>
                    <button class="form-button" id="changePasswordButton">Сменить пароль</button>
                    <button class="form-button" id="blockButton">Заблокировать</button>
                </div>
                <!-- Скроллируемая форма -->
                <div class="scrollable-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="admin_userid" value="<?php echo $_SESSION['userid']; ?>">
                    <!-- Секция профиля -->
                    <div class="profile-section">
                        <div class="user-info">
                            <div class="form-field">
                                <label for="userID">UserID:</label>
                                <input 
                                    type="text" id="userID" 
                                    name="userID" readonly 
                                    value="<?= htmlspecialchars($_POST['userid'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                >
                            </div>
                            <div class="form-field">
                                <label for="login">Логин:</label>
                                <input 
                                    type="text" id="login" 
                                    name="login" 
                                    value="<?= htmlspecialchars($userData['userlogin'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                >
                            </div>
                            <div class="form-field">
                                <label for="lastName">Фамилия:</label>
                                <input 
                                    type="text" id="lastName" 
                                    name="lastName" 
                                    value="<?= htmlspecialchars($userData['family'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                >
                            </div>
                            <div class="form-field">
                                <label for="firstName">Имя:</label>
                                <input 
                                    type="text" id="firstName" 
                                    name="firstName" 
                                    value="<?= htmlspecialchars($userData['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                >
                            </div>
                            <div class="form-field">
                                <label for="fullName">Полное ФИО:</label>
                                <input 
                                    type="text" id="fullName" 
                                    name="fullName" 
                                    value="<?= htmlspecialchars($userData['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                >
                            </div>
                        </div>
                        <div class="profile-picture">
                            <img src="img/user_icon.png" alt="Аватар">
                            <div class="active-status">
                                <label for="active">Активен:</label>
                                <input 
                                    type="checkbox" id="active" 
                                    name="active"
                                    disabled <?= isset($userData['active']) && $userData['active'] ? 'checked' : '' ?>
                                >
                            </div>
                        </div>
                    </div>
                    <div class="form-row spaced-fields">
                        <div class="form-field">
                            <label for="email">E-mail:</label>
                            <input 
                                type="email" id="email" 
                                name="email" 
                                value="<?= htmlspecialchars($userData['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            >
                        </div>
                        <div class="form-field">
                            <label for="phone">Телефон:</label>
                            <input 
                                type="tel" id="phone" 
                                name="phone" 
                                value="<?= htmlspecialchars($userData['telephone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            >
                        </div>
                    </div>
                    <div class="ldap-section">
                        <h3>LDAP</h3>
                        <div class="form-field">
                            <label for="ldapActive">Активирован:</label>
                            <input 
                                type="checkbox" id="ldapActive" 
                                name="ldapActive" disabled 
                                <?= isset($userData['LDAP']) && $userData['LDAP'] ? 'checked' : '' ?>
                            >
                        </div>
                        <div class="form-field">
                            <label for="dn">DN пользователя:</label>
                            <input 
                                type="text" id="dn" 
                                name="dn" readonly 
                                value="<?= htmlspecialchars($userData['DN'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            >
                        </div>
                    </div>
                    <div class="external-interactions">
                        <h3>Внешние взаимодействия</h3>
                        <div class="form-field api-key-field">
                            <button class="form-button" disabled id="getAPIKey">Выдать ключ API</button>
                            <input 
                                type="text" id="apiKey" 
                                name="apiKey" readonly
                            >
                        </div>
                        <div class="form-row">
                            <div class="form-field">
                                <label for="telegramUsername">Telegram Username:</label>
                                <input 
                                    type="text" id="telegramUsername" 
                                    name="telegramUsername" 
                                    value="<?= htmlspecialchars($userData['tg_username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                >
                            </div>
                            <div class="form-field">
                                <label for="telegramID">Telegram ID:</label>
                                <input 
                                    type="text" id="telegramID" 
                                    name="telegramID" 
                                    value="<?= htmlspecialchars($userData['tg_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                >
                            </div>
                        </div>
                    </div>
                </div>
                <?php include ROOT_PATH . '/include/loading.html'; ?>
                <div class="modal-overlay" id="modalOverlay">
                    <div class="passwd-form">
                        <form id="passwdForm">
                            <label for="current_password">Пароль администратора:</label>
                            <input type="password" id="current_password" name="current_password" required>

                            <label for="new_password">Новый пароль:</label>
                            <input type="password" id="new_password" name="new_password" required>

                            <label for="confirm_password">Повторите новый пароль:</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>

                            <button type="button" class="cancel" onclick="closeForm()">Отменить</button>
                            <button type="submit" class="save">Сменить</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
        <?php include ROOT_PATH . '/include/error.php'; ?>
        <?php include ROOT_PATH . '/include/footer.php'; ?>
        <script src="js/edituser/user_acc_save.js"></script>
        <script src="js/edituser/user_block.js"></script>
        <script src="js/edituser/user_update_pass.js"></script>
        <script src="/js/error.js"></script>
    </body>
</html>