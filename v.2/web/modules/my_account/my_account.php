<?php
    // Уникальный идентификатор страницы для проверки привилегий
    $privileges_page = '356a5297-1587-4d79-8f81-b3e1c7e21a73';
    
    $file_path = 'include/platform.php';
        
    if (!file_exists($file_path)) {
        header("Location: /err/50x.html");
        exit();
    }

    require_once $file_path;

    startSessionIfNotStarted();

    $file_path = CHECK_AUTH;
    if (!file_exists($file_path)) {
        header("Location: /err/50x.html");
        exit();
    }
    require_once $file_path;

    // Проверка привилегий для текущей страницы
    $file_path = FROD;

    // Проверка существования файла function.php
    if (!file_exists($file_path)) {
        // Если файл не существует, перенаправляем пользователя на страницу ошибки 503
        header("Location: /err/50x.html");
        exit(); // Прекращаем выполнение скрипта
    }

    // Подключение файла с функциями
    require_once $file_path;



    include "/platform/include/binding/inital_error.php";

    // Логирование успешной инициализации страницы
    logger("DEBUG", "my_account.php успешно инициализирован.");



?>
<!DOCTYPE html>
<html lang="ru">
    <head>
        <?php include ROOT_PATH . '/platform/include/visible/all_head.html'; ?>
        <link rel="stylesheet" href="/platform/include/css/navbar.css"/>
        <link rel="stylesheet" href="/platform/include/css/error.css"/>
        <link rel="stylesheet" href="css/my_account.css"/>
        <title>ЕОС - Моя учетная запись</title>
    </head>
    <body>
        <?php include ROOT_PATH . '/platform/include/visible/eos_header.html'; ?>
        <?php include ROOT_PATH .'/platform/include/visible/navbar.php'; ?>
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
        <?php include ROOT_PATH . '/platform/include/visible/error.php'; ?>
        <?php include ROOT_PATH . '/platform/include/visible/footer.php'; ?>
            <!-- Скрипты -->
        <script src="/platform/include/js/error.js"></script>
        <script src="js/edituser/user_acc_save.js"></script>
        <script src="js/my_account/my_pass_update.js"></script>
    </body>
</html>