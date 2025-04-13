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
    logger("DEBUG", "uesers.php успешно инициализирован.");

?>
<!DOCTYPE html>
    <html lang="ru">
        <head>
            <?php include ROOT_PATH . '/platform/include/visible/all_head.html'; ?>
            <link rel="stylesheet" href="/platform/include/css/navbar.css"/>
            <link rel="stylesheet" href="/platform/include/css/error.css"/>
            <link rel="stylesheet" href="css/users.css"/>
            <title>ЕОС - Управление пользователями</title>
        </head>
        <body>
            <?php include ROOT_PATH . '/platform/include/visible/eos_header.html'; ?>
            <?php include ROOT_PATH .'/platform/include/visible/navbar.php'; ?>

            <main>
                <!-- Контейнер для формы и таблицы -->
                <div class="form-container">
                    <!-- Панель кнопок для управления пользователями -->
                    <div class="button-bar">

                        <?php 
                            $privileges_button = '076a0c70-8cca-4124-b009-97fe44f6c68e';
                            if (checkPrivilege($privileges_bottom)): ?>
                            <button id="addButton">Добавить</button>
                        <?php endif; ?>
                        
                        <?php 
                            $privileges_button = '4e6c22aa-621a-4260-8e26-c2f4177362ba';
                            if (checkPrivilege($privileges_bottom)): ?>
                            <button id="editButton" disabled>Редактировать</button>
                        <?php endif; ?>

                        <?php 
                            $privileges_button = '319b4c95-6beb-4aed-8447-f7338491d2e0';
                            if (checkPrivilege($privileges_bottom)): ?>
                            <button id="blockButton" disabled>Сменить статус пользователя</button>
                        <?php endif; ?>


                        <?php 
                            $privileges_button = '';
                            if (checkPrivilege($privileges_bottom)): ?>
                            <button id="syncLdapButton" disabled>Принудительная синхронизация LDAP</button>
                        <?php endif; ?>

                        <?php 
                            $privileges_button = '';
                            if (checkPrivilege($privileges_bottom)): ?>
                            <button id="ldapSettingsButton" disabled>Настройки LDAP</button>
                        <?php endif; ?>
              
                        <button id="refreshButton" onclick="location.reload()">Обновить</button>
                        
                        <!-- Скрытые поля для CSRF-токена и ID пользователя -->
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="userid" value="<?php echo $_SESSION['userid']; ?>">
                    </div>
                    
                    <!-- Контейнер для таблицы пользователей -->
                    <div class="table-container">
                        <table>
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
                                <?php
                                    // Преобразование массива пользователей в индексированный массив
                                    $users = array_values($users);
                                    // Цикл для отображения каждого пользователя в таблице
                                    foreach ($users as $index => $user):
                                        // Экранирование данных пользователя для безопасного отображения
                                        $fullName = htmlspecialchars($user['full_name'] ?? 'Без имени');
                                        $userLogin = htmlspecialchars($user['userlogin'] ?? 'Без логина');
                                        $isActive = !empty($user['active']) ? 'checked' : '';
                                        $isLdap = !empty($user['add_ldap']) ? 'checked' : '';
                                ?>
                                    <tr>
                                        <!-- Чекбокс для выбора конкретного пользователя -->
                                        <td>
                                            <input type="checkbox" class="userCheckbox" data-userid="<?= htmlspecialchars($user['userid']) ?>">
                                        </td>
                                        <!-- Ссылка на редактирование пользователя -->
                                        <td class="name-cell">
                                            <a href="#" onclick="event.preventDefault(); redirectToEditUser(<?= json_encode($user['userid']) ?>);">
                                                <?= $fullName ?>
                                            </a>
                                        </td>
                                        <!-- Логин пользователя -->
                                        <td><?= $userLogin ?></td>
                                        <!-- Чекбокс для отображения активности пользователя -->
                                        <td>
                                            <input type="checkbox" disabled <?= $isActive ?> class="custom-checkbox status-indicator">
                                        </td>
                                        <!-- Чекбокс для отображения LDAP-статуса пользователя -->
                                        <td>
                                            <input type="checkbox" disabled <?= $isLdap ?> class="custom-checkbox ldap-indicator">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Форма добавления нового пользователя -->
                <div class="add-form-overlay" id="addFormOverlay">
                    <div class="add-form">
                        <form id="addUserForm">
                            <!-- Скрытое поле для CSRF-токена -->
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <!-- Поле для ввода полного имени -->
                            <div class="input-group">
                                <label for="full_name">Полное ФИО:</label>
                                <input type="text" id="full_name" name="full_name" required>
                            </div>
                            <!-- Поле для ввода логина -->
                            <div class="input-group">
                                <label for="userlogin">Логин:</label>
                                <input type="text" id="userlogin" name="userlogin" required>
                            </div>
                            <!-- Поле для ввода пароля с кнопкой генерации пароля -->
                            <div class="input-group password-container">
                                <label for="password">Пароль:</label>
                                <input type="text" id="password" name="password" required>
                                <button type="button" id="generate-password">Сгенерировать</button>
                            </div>
                            <!-- Поле для ввода email -->
                            <div class="input-group">
                                <label for="email">E-mail:</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                            <!-- Выпадающий список для выбора роли пользователя -->
                            <div class="input-group">
                                <label for="role">Полномочия:</label>
                                <select id="role" name="role" required <?php echo (isset($error) ? 'disabled' : ''); ?>>
                                    <?php
                                    // Путь к файлу с шаблонами привилегий
                                    $templatePath = TEMPLATE_PRIVILEGES;

                                    // Проверка существования файла
                                    if (!file_exists($templatePath)) {
                                        // Логирование ошибки, если файл не найден
                                        logger("ERROR", "Ошибка получения шаблонов полномочий - нет файла");
                                        $error = true; // Установка флага ошибки
                                        echo '<option value="default" selected>По умолчанию</option>';
                                    } else {
                                        // Чтение JSON-файла
                                        $jsonData = file_get_contents($templatePath);
                                        $privilegesData = json_decode($jsonData, true);

                                        // Проверка на ошибки декодирования JSON
                                        if (json_last_error() !== JSON_ERROR_NONE || !is_array($privilegesData)) {
                                            // Логирование ошибки, если JSON не удалось декодировать
                                            logger("ERROR", "Ошибка получения шаблонов полномочий - не могу декодировать");
                                            $error = true; // Установка флага ошибки
                                            echo '<option value="default" selected>По умолчанию</option>';
                                        } else {
                                            // Генерация опций для выбора роли
                                            echo '<option value="" disabled selected>Выберите полномочия</option>';
                                            foreach ($privilegesData as $key => $privilege) {
                                                $name = htmlspecialchars($privilege['name']);
                                                echo '<option value="' . $key . '">' . $name . '</option>';
                                            }
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <!-- Кнопки для отмены и создания пользователя -->
                            <div class="button-group">
                                <button type="button" class="cancel">Отменить</button>
                                <button type="submit" class="create">Создать</button>
                            </div>
                        </form>
                    </div>
                </div>

            </main>
        
            <?php include ROOT_PATH . '/platform/include/visible/error.php'; ?>
            <?php include ROOT_PATH . '/platform/include/visible/footer.php'; ?>
            
            <script src="/platform/include/js/error.js"></script>
            <script src="js/users.js"></script>
        </body>
    </html>