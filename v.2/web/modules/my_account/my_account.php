<?php
    // Уникальный идентификатор страницы для проверки привилегий
    $privileges_page = '3fda4364-74ff-4ea7-a4d4-5cca300758a2';

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

    // ==============================================
    // ПОЛУЧЕНИЕ ДАННЫХ ПОЛЬЗОВАТЕЛЯ ЧЕРЕЗ API
    // ==============================================

    // Определяем URL API
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = str_replace([':80',':443'], '', $_SERVER['HTTP_HOST']);
    $apiUrl = "{$protocol}://{$host}:5000/setting/user/data";

    $userData = [];
    $error = null;

    try {
        // Проверяем наличие обязательных данных в сессии
        if (!isset($_SESSION['access_token']) || !isset($_SESSION['userid'])) {
            throw new Exception("Требуется авторизация");
        }

        $access_token = $_SESSION['access_token'];
        $user_id = $_SESSION['userid'];

        // Формируем запрос к API
        $postData = json_encode([
            'access_token' => $access_token,
            'user_id' => $user_id
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
        if (!is_array($responseData)) {
            throw new Exception("Неверный формат ответа от API");
        }

        // Проверяем соответствие user_id
        if (!isset($responseData['userid']) || $responseData['userid'] !== $user_id) {
            throw new Exception("Несоответствие идентификаторов пользователя");
        }

        $userData = $responseData;
        logger("DEBUG", "Данные пользователя успешно получены из API для user_id: {$user_id}");

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

    // ==============================================
    // ПОЛУЧЕНИЕ ПРИВИЛЕГИЙ ПОЛЬЗОВАТЕЛЯ
    // ==============================================
    $privileges = [];
    $hasPageAccess = false;

    try {
        if (isset($_SESSION['access_token'])) {
            $privilegesUrl = "{$protocol}://{$host}:5000/privileges/user_view";
            
            $postData = json_encode([
                'access_token' => $_SESSION['access_token'],
                'user_id' => $_SESSION['userid']
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
                        
                        // Проверяем доступ к текущей странице
                        foreach ($privileges as $priv) {
                            if (isset($priv['id_privilege']) && $priv['id_privilege'] === $privileges_page) {
                                $hasPageAccess = true;
                                break;
                            }
                        }
                    }
                } else {
                    $errorData = json_decode($privilegesResponse, true) ?? [];
                    throw new Exception($errorData['error'] ?? "Ошибка API привилегий (код {$privilegesHttpCode})");
                }
            } else {
                throw new Exception("Ошибка соединения с API привилегий: " . curl_error($ch));
            }
            
            curl_close($ch);
        }
    } catch (Exception $e) {
        logger("ERROR", "Ошибка при получении привилегий: " . $e->getMessage());
    }

    // Если у пользователя нет доступа к этой странице - перенаправляем
    if (!$hasPageAccess) {
        header("Location: /err/403.html");
        exit();
    }

    // Логирование успешной инициализации страницы
    logger("DEBUG", "my_account.php успешно инициализирован");
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
            <?php if ($error): ?>
                <div class="error-message">
                    <strong>Ошибка:</strong> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    <p>Попробуйте обновить страницу или обратитесь в поддержку</p>
                </div>
            <?php endif; ?>
            
            <div class="form-container">
                <h1 class="main-header"> <span class="account-icon"></span> Моя учетная запись</h1>
                <!-- Группа кнопок -->
                <div class="button-group fixed-buttons">
                    <button class="form-button" id="updateButton" onclick="location.reload()">
                        <i class="fas fa-sync-alt"></i> Обновить
                    </button>
                    <button class="form-button" id="saveButton">
                        <i class="fas fa-save"></i> Сохранить
                    </button>
                    <button class="form-button" id="changePasswordButton">
                        <i class="fas fa-key"></i> Сменить пароль
                    </button>
                </div>
                
                <!-- Скроллируемая форма -->
                <div class="scrollable-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    
                    <!-- Секция профиля -->
                    <div class="profile-section">
                        <div class="user-info">
                            <div class="form-field">
                                <label for="userID">UserID:</label>
                                <input type="text" id="userID" name="userID" readonly 
                                    value="<?= htmlspecialchars($userData['userid'] ?? $_SESSION['userid'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="form-field">
                                <label for="login">Логин:</label>
                                <input type="text" id="login" name="login" readonly 
                                    value="<?= htmlspecialchars($userData['userlogin'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="form-field">
                                <label for="lastName">Фамилия:</label>
                                <input type="text" id="lastName" name="lastName" 
                                    value="<?= htmlspecialchars($userData['family'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="form-field">
                                <label for="firstName">Имя:</label>
                                <input type="text" id="firstName" name="firstName" 
                                    value="<?= htmlspecialchars($userData['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="form-field">
                                <label for="fullName">Полное ФИО:</label>
                                <input type="text" id="fullName" name="fullName" 
                                    value="<?= htmlspecialchars($userData['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
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
                    <div class="form-row spaced-fields">
                        <div class="form-field">
                            <label for="email">E-mail:</label>
                            <input type="email" id="email" name="email" 
                                value="<?= htmlspecialchars($userData['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="form-field">
                            <label for="phone">Телефон:</label>
                            <input type="tel" id="phone" name="phone" 
                                value="<?= htmlspecialchars($userData['telephone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
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
                                value="<?= htmlspecialchars($userData['ldap_dn'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
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
                                value="<?= htmlspecialchars($userData['api_key'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="form-row">
                            <div class="form-field">
                                <label for="telegramUsername">Telegram Username:</label>
                                <input type="text" id="telegramUsername" name="telegramUsername" 
                                    value="<?= htmlspecialchars($userData['tg_username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="form-field">
                                <label for="telegramID">Telegram ID:</label>
                                <input type="text" id="telegramID" name="telegramID" 
                                    value="<?= htmlspecialchars($userData['tg_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include ROOT_PATH . '/platform/include/visible/loading.html'; ?>
            
            <!-- Форма смены пароля -->
            <div class="modal-overlay" id="modalOverlay">
                <div class="passwd-form">
                    <form id="passwdForm">
                        <h3><i class="fas fa-key"></i> Смена пароля</h3>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        
                        <div class="form-field">
                            <label for="current_password"><i class="fas fa-lock"></i> Текущий пароль:</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="form-field">
                            <label for="new_password"><i class="fas fa-key"></i> Новый пароль:</label>
                            <input type="password" id="new_password" name="new_password" required>
                        </div>
                        
                        <div class="form-field">
                            <label for="confirm_password"><i class="fas fa-redo"></i> Повторите пароль:</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="button-group">
                            <button type="button" class="cancel" onclick="closeForm()">
                                <i class="fas fa-times"></i> Отменить
                            </button>
                            <button type="submit" class="save">
                                <i class="fas fa-check"></i> Сменить
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
        
        <?php include ROOT_PATH . '/platform/include/visible/error.php'; ?>
        <?php include ROOT_PATH . '/platform/include/visible/footer.php'; ?>
        
        <!-- Скрипты -->
        <script src="/platform/include/js/error.js"></script>
        <script src="js/save.js"></script>
        <script src="js/button_pass_update.js"></script>
        <script src="js/form_pass_update.js"></script>
    </body>
</html>