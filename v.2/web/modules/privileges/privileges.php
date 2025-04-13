<?php
    // Уникальный идентификатор страницы 
    $privileges_page = 'aeef82b7-5083-480e-a59e-507a083a16be';

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
    logger("DEBUG", "privileges.php успешно инициализирован.");
?>
<!DOCTYPE html>
<html lang="ru">
    <head>
    <?php include ROOT_PATH . '/platform/include/visible/all_head.html'; ?>
            <link rel="stylesheet" href="/platform/include/css/navbar.css"/>
            <link rel="stylesheet" href="/platform/include/css/error.css"/>
            <link rel="stylesheet" href="css/privileges.css"/>
            <title>ЕОС - Управление полномочиями</title>
    </head>
    <body>
        <?php include ROOT_PATH . '/platform/include/visible/eos_header.html'; ?>
        <?php include ROOT_PATH .'/platform/include/visible/navbar.php'; ?>
        <main>
            <div class="form-container">
                <div class="button-bar">
                    <div class="button-group">
                        <button id="ViewAllPrivileges">Просмотреть все полномочия</button>
                        <button id="VievPrivileges" disabled>Просмотреть полномочия пользователя</button>

                        <?php 
                            $privileges_button = 'e8e24302-c6e2-4b0d-9b23-4c7119a94756';
                            if (checkPrivilege($privileges_bottom)): ?>
                            <button id="AssignPrivileges" disabled>Назначить полномочия</button>
                            <button id="OffPrivileges" disabled>Снять полномочия</button>
                        <?php endif; ?>

                        <?php 
                            $privileges_button = 'eff8f3c0-97c8-43ef-944e-8eb2dcd1d344';
                            if (checkPrivilege($privileges_bottom)): ?>
                            <button id="CreatePrivileges">Создать полномочия</button>
                            <button id="DeletePrivileges">Удалить полномочия</button>
                        <?php endif; ?>
                        
                        <button id="refreshButton" onclick="location.reload()">Обновить</button>
                    </div>
                    
                    <!-- Поле поиска -->
                    <div class="search-container">
                        <input type="text" id="userSearch" placeholder="Поиск пользователя..." class="search-input">
                    </div>
                    
                    <input type="hidden" name="userid" value="<?php echo $_SESSION['userid']; ?>">
                </div>
                <div class="table-container">
                    <table id="usersTable">
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
                            <?php if (!empty($users)): ?>
                                <?php foreach ($users as $user): ?>
                                    <?php
                                        $fullName = htmlspecialchars($user['full_name'] ?? 'Без имени');
                                        $userLogin = htmlspecialchars($user['userlogin'] ?? 'Без логина');
                                        $userId = htmlspecialchars($user['userid'] ?? 'Без ID');
                                        $isActive = !empty($user['active']) ? 'checked' : '';
                                        $avatar = generateUserAvatar($fullName);
                                    ?>
                                    <tr class="user-row" 
                                        data-fullname="<?= htmlspecialchars(strtolower($fullName)) ?>" 
                                        data-login="<?= htmlspecialchars(strtolower($userLogin)) ?>" 
                                        data-userid="<?= htmlspecialchars(strtolower($userId)) ?>"
                                        data-active="<?= $isActive ? 'активен' : 'неактивен' ?>">
                                        <td>
                                            <input type="checkbox" class="userCheckbox" data-userid="<?= $userId ?>">
                                        </td>
                                        <td class="name-cell">
                                            <?= $avatar ?>
                                            <a href="#" onclick="event.preventDefault(); redirectToEditUser('<?= $userId ?>');">
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
                            <?php else: ?>
                                <tr class="no-data-row">
                                    <td colspan="5" class="no-data-message">Нет данных о пользователях</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Форма создания полномочий -->
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
                    <div class="input-group">
                        <label for="userIDAssign">UserID:</label>
                        <input type="text" id="userIDAssign" name="userIDAssign" readonly>
                    </div>
                    <div class="input-group select-group">
                        <label for="privilegesToAssign">Полномочия:</label>
                        <select id="privilegesToAssign" name="privilegesToAssign[]" multiple>
                            <?php foreach ($name_privileges as $privilege): ?>
                                <option value="<?= $privilege['id_privileges'] ?>"><?= $privilege['name_privileges'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="button-group">
                        <button type="button" id="submitAssignPrivilegesForm">Назначить полномочия</button>
                        <button type="button" id="cancelAssignPrivilegesForm">Отменить</button>
                    </div>
                </form>
            </div>
        </main>
        
        <?php include ROOT_PATH . '/platform/include/visible/error.php'; ?>
        <?php include ROOT_PATH . '/platform/include/visible/footer.php'; ?>
           
        <script src="/platform/include/js/error.js"></script>
        <script src="js/find_privileges.js"></script>
    </body>
</html>