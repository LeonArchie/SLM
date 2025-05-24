<?php
    // Уникальный идентификатор страницы для проверки привилегий
    $privileges_page = 'da137713-83fe-4325-868f-14b967dbf17c';

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

    // Получаем список пользователей из API
    $users = [];
    try {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = str_replace([':80',':443'], '', $_SERVER['HTTP_HOST']);
        $apiUrl = "{$protocol}://{$host}:5000/setting/user/list";
        $requestData = [
            'user_id' => $_SESSION['userid'],
            'access_token' => $_SESSION['access_token'] ?? ''
        ];

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("API request failed with HTTP code: {$httpCode}");
        }

        $responseData = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Failed to decode API response");
        }

        if ($responseData['status'] !== 'success') {
            throw new Exception($responseData['error'] ?? 'Unknown API error');
        }

        $users = $responseData['users'] ?? [];
    } catch (Exception $e) {
        logger("ERROR", "Ошибка при получении списка пользователей: " . $e->getMessage());
        // Выводим сообщение об ошибке на фронтенд
       $error_message = "Не удалось загрузить данные.";
    }
    
    /**
     * Генерирует HTML для аватарки на основе имени пользователя
     */
    function generateUserAvatar($fullName) {
        $initials = '';
        $parts = explode(' ', $fullName);
        
        // Берем первую букву первого слова
        if (count($parts) > 0 && !empty($parts[0])) {
            $initials .= mb_substr($parts[0], 0, 1);
        }
        
        // Берем первую букву последнего слова (если есть)
        if (count($parts) > 1 && !empty($parts[count($parts)-1])) {
            $initials .= mb_substr($parts[count($parts)-1], 0, 1);
        }
        
        // Если не получилось извлечь инициалы, используем "?"
        if (empty($initials)) {
            $initials = '?';
        }
        
        // Генерируем цвет на основе хеша имени
        $hash = crc32($fullName);
        $colors = ['#3498db', '#2ecc71', '#e74c3c', '#f39c12', '#9b59b6', '#1abc9c', '#d35400'];
        $color = $colors[abs($hash) % count($colors)];
        
        return '<div class="user-avatar" style="background-color: '.$color.'">'.mb_strtoupper($initials).'</div>';
    }
    include "/platform/include/binding/inital_error.php";

    // Логирование успешной инициализации страницы
    logger("DEBUG", "uesers.php успешно инициализирован.");
?>
<!DOCTYPE html>
<html lang="ru">
    <head>
        <?php include ROOT_PATH . '/platform/include/visible/all_head.html'; ?>
        <link rel="stylesheet" href="/platform/include/css/navbar.css"/>
        <link rel="stylesheet" href="/platform/include/css/error.css"/>
        <link rel="stylesheet" href="css/users.css"/>
        <link rel="stylesheet" href="css/form.css"/>
        <title>ЕОС - Управление пользователями</title>
    </head>
    <body>
        <?php include ROOT_PATH . '/platform/include/visible/eos_header.html'; ?>
        <?php include ROOT_PATH .'/platform/include/visible/navbar.php'; ?>

        <main>
            <!-- Контейнер для формы и таблицы -->
            <div class="form-container">
                <h1 class="main-header"> <span class="user-icon"></span> Управление пользователями </h1>
                <!-- Панель кнопок для управления пользователями -->
                <div class="button-bar">
                    <div class="button-group">
                        <?php 
                            $privileges_button = '076a0c70-8cca-4124-b009-97fe44f6c68e';
                            if (checkPrivilege( $privileges_button)): ?>
                            <button id="addButton">Добавить</button>
                        <?php endif; ?>
                        
                        <?php 
                            $privileges_button = '4e6c22aa-621a-4260-8e26-c2f4177362ba';
                            if (checkPrivilege( $privileges_button)): ?>
                            <button id="editButton" disabled>Редактировать</button>
                        <?php endif; ?>

                        <?php 
                            $privileges_button = '319b4c95-6beb-4aed-8447-f7338491d2e0';
                            if (checkPrivilege( $privileges_button)): ?>
                            <button id="blockButton" disabled>Сменить статус пользователя</button>
                        <?php endif; ?>

                        <?php 
                            $privileges_button = 'a3999d28-1b81-47ac-a0e7-5898ded6cbfa';
                            if (checkPrivilege( $privileges_button)): ?>
                            <button id="syncLdapButton" disabled>Принудительная синхронизация LDAP</button>
                        <?php endif; ?>

                        <?php 
                            $privileges_button = '';
                            if (checkPrivilege( $privileges_button)): ?>
                            <button id="ldapSettingsButton" disabled>Настройки LDAP</button>
                        <?php endif; ?>
              
                        <button id="refreshButton" onclick="location.reload()">Обновить</button>
                    </div>
                    
                    <!-- Строка поиска -->
                    <div class="search-container">
                        <input type="text" id="userSearch" placeholder="Поиск пользователя..." class="search-input">
                    </div>
                    
                    <!-- Скрытые поля для CSRF-токена и ID пользователя -->
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="userid" value="<?php echo $_SESSION['userid']; ?>">
                </div>
                
                <!-- Контейнер для таблицы пользователей -->
                <div class="table-container">
                    <table id="usersTable">
                        <thead>
                            <tr>
                                <!-- Чекбокс для выбора всех пользователей -->
                                <th><input type="checkbox" id="selectAll"></th>
                                <th>Полное ФИО</th>
                                <th>Логин</th>
                                <th>Активен</th>
                                <th>LDAP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($users)): ?>
                                <?php foreach ($users as $user): ?>
                                    <?php
                                        $fullName = htmlspecialchars($user['full_name'] ?? 'Без имени');
                                        $userLogin = htmlspecialchars($user['userlogin'] ?? 'Без логина');
                                        $isActive = !empty($user['active']) ? 'checked' : '';
                                        $isLdap = !empty($user['add_ldap']) ? 'checked' : '';
                                        $avatar = generateUserAvatar($fullName);
                                    ?>
                                    <tr class="user-row" data-fullname="<?= htmlspecialchars(strtolower($fullName)) ?>" data-login="<?= htmlspecialchars(strtolower($userLogin)) ?>">
                                        <td>
                                            <input type="checkbox" class="userCheckbox" data-userid="<?= htmlspecialchars($user['userid']) ?>">
                                        </td>
                                        <td class="name-cell">
                                            <?= $avatar ?>
                                            <a href="#" onclick="event.preventDefault(); redirectToEditUser(<?= json_encode($user['userid']) ?>);">
                                                <?= $fullName ?>
                                            </a>
                                        </td>
                                        <td><?= $userLogin ?></td>
                                        <td>
                                            <input type="checkbox" disabled <?= $isActive ?> class="custom-checkbox status-indicator">
                                        </td>
                                        <td>
                                            <input type="checkbox" disabled <?= $isLdap ?> class="custom-checkbox ldap-indicator">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">Нет данных о пользователях</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php include 'include/form.php'; ?>
        </main>
    
        <?php include ROOT_PATH . '/platform/include/visible/error.php'; ?>
        <?php include ROOT_PATH . '/platform/include/visible/footer.php'; ?>
        
        <script src="/platform/include/js/error.js"></script>
        <script src="/platform/include/js/check_jwt.js"></script>
        <script src="js/users.js"></script>
        <script src="js/find_users.js"></script>
        <script src="js/user_block.js"></script>
        <script src="js/open_create_user.js"></script>
        <script src="js/create_user.js"></script>
        <script src="js/edit_user.js"></script>
    </body>
</html>