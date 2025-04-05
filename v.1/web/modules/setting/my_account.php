<?php
    // Уникальный идентификатор страницы для проверки привилегий
    $privileges_page = '356a5297-1587-4d79-8f81-b3e1c7e21a73';

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
    
    // Путь к файлу load_account.php
    $file_path = __DIR__ . '/back/edituser/load_account.php';
    // Проверка существования файла load_account.php
    if (!file_exists($file_path)) {
        // Если файл не существует, перенаправляем на страницу ошибки 500
        header("Location: /err/50x.html");
        exit();
    }
    // Подключение файла load_account.php
    require_once $file_path;

    // Инициализация сессии
    startSessionIfNotStarted();
    // Проверка авторизации пользователя
    checkAuth();
    // Генерация CSRF-токена для защиты от атак
    csrf_token();

    // Проверка привилегий пользователя для доступа к странице
    FROD($privileges_page);

    // Инициализация переменной для хранения сообщения об ошибке
    $error_message = "";
    // Проверка наличия ошибки в GET-параметрах
    if (isset($_GET['error'])) {
        $raw_error = $_GET['error']; // Сохраняем сырое значение ошибки
        // Экранируем специальные символы для безопасного вывода на страницу
        $error_message = htmlspecialchars($raw_error, ENT_QUOTES, 'UTF-8');
    }
    
    // Получение идентификатора пользователя из сессии
    $userid = $_SESSION['userid'];
    
    try {
        // Получение данных пользователя по его идентификатору
        $userData = getUserData($userid);
    } catch (Exception $e) {
        // Логирование ошибки при получении данных пользователя
        logger("ERROR", "Ошибка при получении данных о пользователе:". $e->getMessage());
        audit("ERROR", "Ошибка при получении данных о пользователе:". $e->getMessage());
        // Перенаправление на страницу ошибки 500 в случае исключения
        header("Location: /err/50x.html");
        exit();
    }

    // Проверка наличия ошибки в данных пользователя
    if (isset($userData['error'])) {
        // Установка сообщения об ошибке, если оно есть в данных пользователя
        $error_message = $userData['error'];
        // Логирование ошибки
        logger("ERROR", "Ошибка в данных пользователя: " . $error_message);
    }
    logger("DEBUG", "my_account.php успешно инициализирован.");
?>
<!DOCTYPE html>
<html lang="ru">
    <head>
        <?php include ROOT_PATH . '/include/all_head.html'; ?>
        <!-- Подключение стилей -->
        <link rel="stylesheet" href="/css/navbar.css"/>
        <link rel="stylesheet" href="css/my_account.css"/>
        <link rel="stylesheet" href="/css/error.css"/>
    </head>
    <body>
        <?php include ROOT_PATH . '/include/eos_header.html'; ?>
        <?php include ROOT_PATH .'/include/navbar.php'; ?>
        <main>
            <div class="form-container">
                <!-- Группа кнопок -->
                <div class="button-group fixed-buttons">
                    <button class="form-button" id="updateButton" onclick="location.reload()">Обновить страницу</button>
                    <button class="form-button" id="saveButton">Сохранить</button>
                    <button class="form-button" id="changePasswordButton">Сменить пароль</button>
                </div>
                <!-- Скроллируемая форма -->
                <div class="scrollable-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <!-- Секция профиля -->
                    <div class="profile-section">
                        <div class="user-info">
                            <div class="form-field">
                                <label for="userID">UserID:</label>
                                <input 
                                    type="text" id="userID" 
                                    name="userID" readonly 
                                    value="<?= htmlspecialchars($_SESSION['userid'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                >
                            </div>
                            <div class="form-field">
                                <label for="login">Логин:</label>
                                <input 
                                    type="text" id="login" 
                                    name="login" readonly 
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
                                    name="active" class="custom-checkbox user-active"
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
                                    class="custom-checkbox ldap-active"
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
                                <button class="form-button" disabled id="getAPIKey">Получить ключ API</button>
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
                </div>
                <?php include ROOT_PATH . '/include/loading.html'; ?>
                <!-- Подложка для формы -->
                <div class="modal-overlay" id="modalOverlay">
                    <div class="passwd-form">
                        <form id="passwdForm">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <label for="current_password">Текущий пароль:</label>
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
            <!-- Скрипты -->
        <script src="js/edituser/user_acc_save.js"></script>
        <script src="js/my_account/my_pass_update.js"></script>
        <script src="/js/error.js"></script>
    </body>
</html>