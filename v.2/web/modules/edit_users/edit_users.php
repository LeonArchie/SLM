<?php
    // Уникальный идентификатор страницы для проверки привилегий
    $privileges_page = '4e6c22aa-621a-4260-8e26-c2f4177362ba';

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

    if (!file_exists($file_path)) {
        header("Location: /err/50x.html");
        exit();
    }

    require_once $file_path;

    include "/platform/include/binding/inital_error.php";

    // Проверяем, что передан userid
    if (!isset($_GET['userid']) || empty($_GET['userid'])) {
        header("Location: /err/400.html");
        exit();
    }

    $user_check_id = $_GET['userid'];
    $user_admin_id = $_SESSION['userid'] ?? null;
    $access_token = $_SESSION['access_token'] ?? null;

    if (!$user_admin_id || !$access_token) {
        header("Location: /logout.php");
        exit();
    }

    // ==============================================
    // ПОЛУЧЕНИЕ ДАННЫХ ПОЛЬЗОВАТЕЛЯ ЧЕРЕЗ API
    // ==============================================

    // Определяем URL API
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = str_replace([':80',':443'], '', $_SERVER['HTTP_HOST']);
    $apiUrl = "{$protocol}://{$host}:5000/setting/user/full_data";

    $userData = [];
    $addressBook = [];
    $error = null;

    try {
        // Формируем запрос к API для получения данных пользователя
        $postData = json_encode([
            'access_token' => $access_token,
            'user_admin_id' => $user_admin_id,
            'user_check_id' => $user_check_id
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Content-Length: ' . strlen($postData)
            ],
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 2,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception("Ошибка соединения с API: " . curl_error($ch));
        }

        curl_close($ch);

        // Проверяем код ответа
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true) ?? [];
            throw new Exception($errorData['error'] ?? "Ошибка API (код {$httpCode})");
        }

        // Декодируем и проверяем ответ
        $responseData = json_decode($response, true);
        if (!is_array($responseData) || !isset($responseData['status']) || !$responseData['status']) {
            throw new Exception("Неверный формат ответа от API или доступ запрещен");
        }

        // Проверяем наличие данных пользователя
        if (!isset($responseData['user_data']) || !is_array($responseData['user_data'])) {
            throw new Exception("Отсутствуют данные пользователя в ответе API");
        }

        $userData = $responseData['user_data'];
        logger("DEBUG", "Данные пользователя успешно получены из API для user_id: {$user_check_id}");

        // Получаем адресную книгу
        $addressBookResponse = getAddressBook($access_token, $user_admin_id);
        if ($addressBookResponse['status']) {
            $addressBook = $addressBookResponse['contacts'] ?? [];
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
        logger("ERROR", "Ошибка при получении данных пользователя: " . $error);
        
        // Если это ошибка авторизации - разлогиниваем
        if (strpos($error, "Требуется авторизация") !== false || 
            strpos($error, "Invalid token") !== false) {
            header("Location: /logout.php");
            exit();
        }
    }

    // Получаем имя создателя из адресной книги
    $creatorName = 'Пользователь неизвестен';
    if (!empty($userData['reg_user_id']) && !empty($addressBook)) {
        foreach ($addressBook as $contact) {
            if ($contact['user_id'] === $userData['reg_user_id']) {
                $creatorName = $contact['full_name'] ?? 'Пользователь неизвестен';
                break;
            }
        }
    }

    // Получаем имя редактора из адресной книги
    $editorName = 'Пользователь неизвестен';
    if (!empty($userData['changing']) && !empty($addressBook)) {
        foreach ($addressBook as $contact) {
            if ($contact['user_id'] === $userData['changing']) {
                $editorName = $contact['full_name'] ?? 'Пользователь неизвестен';
                break;
            }
        }
    }

    // ==============================================
    // ПОЛУЧЕНИЕ ПРИВИЛЕГИЙ ПОЛЬЗОВАТЕЛЯ
    // ==============================================
    $privileges = [];

    try {
        $privilegesUrl = "{$protocol}://{$host}:5000/privileges/user_view";
        
        $postData = json_encode([
            'access_token' => $access_token,
            'user_id' => $user_check_id // Используем user_check_id вместо user_admin_id
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $privilegesUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Content-Length: ' . strlen($postData)
            ],
            CURLOPT_TIMEOUT => 3,
            CURLOPT_CONNECTTIMEOUT => 2,
        ]);

        $privilegesResponse = curl_exec($ch);
        $privilegesHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (!curl_errno($ch)) {
            if ($privilegesHttpCode === 200) {
                $privilegesData = json_decode($privilegesResponse, true);
                if (isset($privilegesData['privileges']) && is_array($privilegesData['privileges'])) {
                    $privileges = $privilegesData['privileges'];
                    logger("DEBUG", "Получены привилегии пользователя: " . count($privileges) . " шт.");
                    
                }
            } else {
                $errorData = json_decode($privilegesResponse, true) ?? [];
                throw new Exception($errorData['error'] ?? "Ошибка API привилегий (код {$privilegesHttpCode})");
            }
        } else {
            throw new Exception("Ошибка соединения с API привилегий: " . curl_error($ch));
        }
        
        curl_close($ch);
    } catch (Exception $e) {
        logger("ERROR", "Ошибка при получении привилегий: " . $e->getMessage());
    }

    // Логирование успешной инициализации страницы
    logger("DEBUG", "edit_users.php успешно инициализирован для редактирования пользователя {$user_check_id}");

    // Функция для получения адресной книги
    function getAddressBook($accessToken, $userId) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = str_replace([':80',':443'], '', $_SERVER['HTTP_HOST']);
        $apiUrl = "{$protocol}://{$host}:5000/adresbook/list";
        
        try {
            $postData = json_encode([
                'access_token' => $accessToken,
                'user_id' => $userId
            ]);
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postData,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Content-Length: ' . strlen($postData)
                ],
                CURLOPT_TIMEOUT => 3,
                CURLOPT_CONNECTTIMEOUT => 2,
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                throw new Exception("Ошибка соединения: " . curl_error($ch));
            }
            
            curl_close($ch);
            
            if ($httpCode !== 200) {
                return ['status' => false, 'error' => "HTTP код: {$httpCode}"];
            }
            
            $data = json_decode($response, true);
            if (!isset($data['contacts'])) {
                return ['status' => false, 'error' => 'Неверный формат ответа'];
            }
            
            return ['status' => true, 'contacts' => $data['contacts']];
            
        } catch (Exception $e) {
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    // Функция для форматирования NULL значений
    function formatValue($value) {
        return $value === null ? '' : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
?>
<!DOCTYPE html>
<html lang="ru">
    <head>
        <?php include ROOT_PATH . '/platform/include/visible/all_head.html'; ?>
        <link rel="stylesheet" href="/platform/include/css/navbar.css"/>
        <link rel="stylesheet" href="/platform/include/css/error.css"/>
        <link rel="stylesheet" href="css/edit_users.css"/>
        <title>ЕОС - Редактирование пользователя</title>
    </head>
    <body>
        <?php include ROOT_PATH . '/platform/include/visible/eos_header.html'; ?>
        <?php include ROOT_PATH .'/platform/include/visible/navbar.php'; ?>
        
        <main>
            <?php if ($error):
                    $error_message = "Попробуйте обновить страницу или обратитесь в поддержку";
            endif; ?>
            
            <div class="form-container">
                <h1 class="main-header"> <span class="account-icon"></span> Редактирование пользователя</h1>
                
                <!-- Группа кнопок -->
                <div class="button-group fixed-buttons">
                    <button class="form-button" id="backButton">
                        <i class="fas fa-sync-alt"></i> Назад
                    </button>
                    <button class="form-button" id="changePasswordButton">
                        <i class="fas fa-key"></i> Сбросить пароль пользователя
                    </button>
                    <button class="form-button" id="blockButton">
                        <i class="fas fa-key"></i> Сменить статус пользователя
                    </button>
                    <button class="form-button" id="saveButton">
                        <i class="fas fa-save"></i> Сохранить
                    </button>
                    <button class="form-button" id="updateButton" onclick="location.reload()">
                        <i class="fas fa-sync-alt"></i> Обновить
                    </button>
                </div>
                
                <!-- Скроллируемая форма -->
                <div class="scrollable-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="user_admin_id" value="<?= htmlspecialchars($user_admin_id ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="user_check_id" value="<?= htmlspecialchars($user_check_id ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    
                    <!-- Секция профиля -->
                    <div class="profile-section">
                        <div class="user-info">
                            <div class="form-field">
                                <label for="userID">UserID:</label>
                                <input type="text" id="userID" name="userID" readonly 
                                    value="<?= formatValue($userData['userid'] ?? null) ?>">
                            </div>
                            <div class="form-field">
                                <label for="login">Логин:</label>
                                <input type="text" id="login" name="login" 
                                    value="<?= formatValue($userData['userlogin'] ?? null) ?>">
                            </div>
                            <div class="form-field">
                                <label for="lastName">Фамилия:</label>
                                <input type="text" id="lastName" name="lastName" 
                                    value="<?= formatValue($userData['family'] ?? null) ?>">
                            </div>
                            <div class="form-field">
                                <label for="firstName">Имя:</label>
                                <input type="text" id="firstName" name="firstName" 
                                    value="<?= formatValue($userData['name'] ?? null) ?>">
                            </div>
                            <div class="form-field">
                                <label for="fullName">Полное ФИО:</label>
                                <input type="text" id="fullName" name="fullName" 
                                    value="<?= formatValue($userData['full_name'] ?? null) ?>">
                            </div>
                            <div class="form-field">
                                <label for="department">Подразделение:</label>
                                <input type="text" id="department" name="department" 
                                value="<?= formatValue($userData['department'] ?? null) ?>">
                            </div>
                            <div class="form-field">
                                <label for="post">Должность:</label>
                                <input type="text" id="post" name="post" 
                                value="<?= formatValue($userData['post'] ?? null) ?>">
                            </div>
                        </div>
                        <div class="profile-picture">
                            <img src="img/user_icon.png" alt="Аватар" id="userAvatar">
                            <div class="active-status">
                                <label for="active">Активен:</label>
                                <input type="checkbox" id="active" name="active" class="custom-checkbox user-active" disabled 
                                    <?= isset($userData['active']) && $userData['active'] ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Контактные данные -->
                    <div class="contacts-section">
                        <h3><i class="fas fa-address-book"></i> Контакты</h3>
                        <div class="contacts-container">
                            <!-- Левая колонка -->
                            <div class="contact-column">
                                <div class="contact-field">
                                    <label for="personal_mail">Личный e-mail:</label>
                                    <input type="email" id="personal_mail" name="personal_mail" 
                                        value="<?= formatValue($userData['personal_mail'] ?? null) ?>">
                                </div>
                                
                                <div class="checkbox-row">
                                    <label for="visible_personal_mail">Показывать личный e-mail:</label>
                                    <div class="checkbox-container">
                                        <input type="checkbox" id="visible_personal_mail" name="visible_personal_mail" class="custom-checkbox"
                                            <?= isset($userData['visible_personal_mail']) && $userData['visible_personal_mail'] ? 'checked' : '' ?>>
                                    </div>
                                </div>
                                
                                <div class="contact-field">
                                    <label for="user_off_email">Корпоративный e-mail:</label>
                                    <input type="email" id="user_off_email" name="user_off_email" 
                                        value="<?= formatValue($userData['user_off_email'] ?? null) ?>">
                                </div>
                            </div>
                            
                            <!-- Правая колонка -->
                            <div class="contact-column">
                                <div class="contact-field">
                                    <label for="telephone">Личный телефон:</label>
                                    <input type="tel" id="telephone" name="telephone" 
                                        value="<?= formatValue($userData['telephone'] ?? null) ?>">
                                </div>
                                
                                <div class="checkbox-row">
                                    <label for="visible_telephone">Показывать личный телефон:</label>
                                    <div class="checkbox-container">
                                        <input type="checkbox" id="visible_telephone" name="visible_telephone" class="custom-checkbox"
                                            <?= isset($userData['visible_telephone']) && $userData['visible_telephone'] ? 'checked' : '' ?>>
                                    </div>
                                </div>
                                
                                <div class="contact-field">
                                    <label for="corp_phone">Рабочий телефон:</label>
                                    <input type="tel" id="corp_phone" name="corp_phone" 
                                        value="<?= formatValue($userData['corp_phone'] ?? null) ?>">
                                </div>
                                
                                <div class="checkbox-row">
                                    <label for="visible_corp_phone">Показывать рабочий телефон:</label>
                                    <div class="checkbox-container">
                                        <input type="checkbox" id="visible_corp_phone" name="visible_corp_phone" class="custom-checkbox"
                                            <?= isset($userData['visible_corp_phone']) && $userData['visible_corp_phone'] ? 'checked' : '' ?>>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Полномочия в системе -->
                    <div class="privileges-section">
                        <h3><i class="fas fa-user-shield"></i> Полномочия в системе</h3>
                        <?php if (!empty($privileges)): ?>
                            <div class="privileges-container">
                                <?php foreach ($privileges as $privilege): ?>
                                    <?php if (isset($privilege['name_privilege'])): ?>
                                        <div class="privilege-item">
                                            <i class="fas fa-check-circle privilege-icon"></i>
                                            <span class="privilege-name"><?= htmlspecialchars($privilege['name_privilege'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="no-privileges">Нет назначенных полномочий</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- LDAP секция -->
                    <div class="ldap-section">
                        <h3><i class="fas fa-address-book"></i> LDAP</h3>
                        <div class="form-field">
                            <label for="ldapActive">Активирован:</label>
                            <input type="checkbox" id="ldapActive" name="ldapActive" disabled class="custom-checkbox ldap-active"
                                <?= isset($userData['add_ldap']) && $userData['add_ldap'] ? 'checked' : '' ?>>
                        </div>
                        <div class="form-field">
                            <label for="dn">DN пользователя:</label>
                            <input type="text" id="dn" name="dn" readonly 
                                value="<?= formatValue($userData['ldap_dn'] ?? null) ?>">
                        </div>
                    </div>
                    
                    <!-- Внешние сервисы -->
                    <div class="external-interactions">
                        <h3><i class="fas fa-external-link-alt"></i> Внешние взаимодействия</h3>
                        <div class="form-field api-key-field">
                            <button class="form-button" id="getAPIKey" disabled>
                                <i class="fas fa-key"></i> Получить ключ API
                            </button>
                            <input type="text" id="apiKey" name="apiKey" readonly
                                value="<?= formatValue($userData['api_key'] ?? null) ?>">
                        </div>
                        <div class="form-row">
                            <div class="form-field">
                                <label for="telegramUsername">Telegram Username:</label>
                                <input type="text" id="telegramUsername" name="telegramUsername" 
                                    value="<?= formatValue($userData['tg_username'] ?? null) ?>">
                            </div>
                            <div class="form-field">
                                <label for="telegramID">Telegram ID:</label>
                                <input type="text" id="telegramID" name="telegramID" 
                                    value="<?= formatValue($userData['tg_id'] ?? null) ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Служебная информация -->
                    <div class="service-info-section">
                        <h3><i class="fas fa-info-circle"></i> Служебная информация</h3>
                        <div class="service-info-container">
                            <div class="form-field">
                                <label for="creator">Создал:</label>
                                <input type="text" id="creator" name="creator" readonly 
                                    value="<?= htmlspecialchars($creatorName, ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="form-field">
                                <label for="creator_guid">GUID создателя:</label>
                                <input type="text" id="creator_guid" name="creator_guid" readonly 
                                    value="<?= formatValue($userData['reg_user_id'] ?? null) ?>">
                            </div>
                            <div class="form-field">
                                <label for="created_at">Когда создан:</label>
                                <input type="text" id="created_at" name="created_at" readonly 
                                    value="<?= formatValue($userData['regtimes'] ?? null) ?>">
                            </div>
                            <div class="form-field">
                                <label for="editor">Редактировал:</label>
                                <input type="text" id="editor" name="editor" readonly 
                                    value="<?= htmlspecialchars($editorName, ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="form-field">
                                <label for="editor_guid">GUID редактирующего:</label>
                                <input type="text" id="editor_guid" name="editor_guid" readonly 
                                    value="<?= formatValue($userData['changing'] ?? null) ?>">
                            </div>
                            <div class="form-field">
                                <label for="updated_at">Время изменения:</label>
                                <input type="text" id="updated_at" name="updated_at" readonly 
                                    value="<?= formatValue($userData['changing_timestamp'] ?? null) ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include ROOT_PATH . '/platform/include/visible/loading.html'; ?>
            <?php include 'include/form.php'?>
        </main>
        
        <?php include ROOT_PATH . '/platform/include/visible/error.php'; ?>
        <?php include ROOT_PATH . '/platform/include/visible/footer.php'; ?>
        
        <!-- Скрипты -->
        <script src="/platform/include/js/error.js"></script>
        <script src="/platform/include/js/check_jwt.js"></script>
        <script src="js/back.js"></script>
        <script src="js/button_pass_update.js"></script>
        <script src="js/admin_user_block.js"></script>
        <script src="js/save.js"></script>
        <script src="js/save_button.js"></script>
    </body>
</html>