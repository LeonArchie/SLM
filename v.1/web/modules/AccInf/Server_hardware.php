<?php
    // Уникальный идентификатор страницы для проверки привилегий
    $privileges_page = '16bdb437-08e7-4783-8945-73618eab30e7';

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

    // Инициализация сессии
    startSessionIfNotStarted();
    // Проверка авторизации пользователя
    checkAuth();
    // Генерация CSRF-токена для защиты от атак
    csrf_token();

    // Проверка привилегий пользователя для доступа к странице
    FROD($privileges_page);

        // Подключение к базе данных с обработкой исключений
    try {
        $pdo = connectToDatabase();
    } catch (PDOException $e) {
        // Логирование ошибки подключения к базе данных
        logger("ERROR", "Ошибка подключения к базе данных: " . $e->getMessage());
        // Вывод сообщения об ошибке
        header("Location: /err/50x.html");
        exit();
    }

    // Подготовка запроса для получения данных о серверах
    $stmt = $pdo->prepare('SELECT servers."Name", Status, serv_id, ip_addr, servers."Domain", servers."Demon", servers."validate", servers."stand" FROM servers');

    // Выполнение запроса и проверка на ошибки
    if (!$stmt->execute()) {
        // Логирование ошибки, если запрос не выполнился
        logger("ERROR", "Ошибка при выполнении запроса к таблице servers.");
        header("Location: /err/50x.html");
        exit();
    }

    // Получение всех записей о серверах
    $servers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    logger("DEBUG", "Получен ответ на запрос:" . print_r($servers, true));

    // Инициализация переменной для хранения сообщения об ошибке
    $error_message = "";
    // Проверка наличия ошибки в GET-параметрах
    if (isset($_GET['error'])) {
        $raw_error = $_GET['error']; // Сохраняем сырое значение ошибки
        // Экранируем специальные символы для безопасного вывода на страницу
        $error_message = htmlspecialchars($raw_error, ENT_QUOTES, 'UTF-8');
    }
?>
<!DOCTYPE html>
<html lang="ru">
    <head>
        <!-- Подключение платформы -->
        <?php include ROOT_PATH . '/include/all_head.html'; ?>
        <link rel="stylesheet" href="/css/navbar.css"/>
        <link rel="stylesheet" href="/css/error.css"/>
        <link rel="stylesheet" href="css/Server_hardware.css"/>
    </head>
    <body>
        <!-- Подключение платформы -->
        <?php include ROOT_PATH . '/include/eos_header.html'; ?>
        <?php include ROOT_PATH .'/include/navbar.php'; ?>
        <main>
            <div class="form-container">
                <div class="button-bar">
                    <button id="AddServers">Добавить оборудование</button>
                    <button id="VievCardServer" disabled>Просмотреть карточку оборудования</button>
                    <button id="GlobalCheck">Глобальная проверка конфиликтов</button>
                    <button id="refreshButton" onclick="location.reload()">Обновить</button>
                    <input type="hidden" name="userid" value="<?php echo $_SESSION['userid']; ?>">
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
                                <th>Наименование оборудования</th>
                                <th>Стенд</th>
                                <th>Статус</th>
                                <th>Ip Адрес</th>
                                <th>Домен</th>
                                <th>Демон подключен</th>
                                <th>Валидация</th>
                                <th>ID Оборудования</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                foreach ($servers as $server):
                                    $name = htmlspecialchars($server['name'] ?? 'Не указано');
                                    $stand = htmlspecialchars($server['stand'] ?? 'Не указан');
                                    $status = htmlspecialchars($server['status'] ?? 'Неизвестен');
                                    $servId = htmlspecialchars($server['serv_id'] ?? 'Без ID');
                                    $ipAddr = htmlspecialchars($server['ip_addr'] ?? 'Не указан');
                                    $domain = htmlspecialchars($server['domain'] ?? 'Не указан');
                                    $demonConnected = !empty($server['demon']) ? 'checked' : '';
                                    $validateChecked = !empty($server['validate']) ? 'checked' : '';
                            ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="serverCheckbox" data-serverid="<?= $servId ?>">
                                </td>
                                <td class="name-cell">
                                    <a href="#" onclick="event.preventDefault(); redirectToServerCard(<?= json_encode($servId) ?>);">
                                        <?= $name ?>
                                    </a>
                                </td>
                                <td><?= $stand ?></td>
                                <td><?= $status ?></td>
                                <td><?= $ipAddr ?></td>
                                <td><?= $domain ?></td>
                                <td>
                                    <input type="checkbox" disabled <?= $demonConnected ?> class="custom-checkbox demon-indicator">
                                </td>
                                <td>
                                    <input type="checkbox" disabled <?= $validateChecked ?> class="custom-checkbox validate-indicator">
                                </td>
                                <td><?= $servId ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
        <!-- Модальное окно добавления оборудования -->
        <div id="addServerModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Добавить оборудование</h3>
                    <span class="close-modal">&times;</span>
                </div>
                <div class="modal-body">
                    <form id="addServerForm">
                        <div class="form-group">
                            <label for="serverName">Наименование оборудования*</label>
                            <input type="text" id="serverName" name="serverName" maxlength="50" required 
                                pattern="[a-zA-Zа-яА-Я0-9\-_ ]+" 
                                title="Разрешены русские и латинские символы, цифры, тире и подчеркивание">
                        </div>
                        
                        <div class="form-group">
                            <label for="serverStatus">Статус*</label>
                            <select id="serverStatus" name="serverStatus" required>
                                <option value="" selected disabled>Выберите статус</option>
                                <option value="Планирование">Планирование</option>
                                <option value="Установка">Установка</option>
                                <option value="Настройка">Настройка</option>
                                <option value="Опытная эксплуатация">Опытная эксплуатация</option>
                                <option value="Промышленная эксплуатация">Промышленная эксплуатация</option>
                                <option value="Резерв">Резерв</option>
                                <option value="Выведен из эксплуатации">Выведен из эксплуатации</option>
                                <option value="Списан">Списан</option>
                                <option value="Утилизирован">Утилизирован</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="ipv6Checkbox" name="ipv6Checkbox">
                                Использовать IPv6
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label for="ipAddress">IP Адрес</label>
                            <input type="text" id="ipAddress" name="ipAddress" maxlength="45" 
                                pattern="^([0-9]{1,3}\.){3}[0-9]{1,3}$" 
                                title="Формат IPv4: xxx.xxx.xxx.xxx">
                        </div>
                        
                        <div class="form-group">
                            <label for="domain">Домен</label>
                            <input type="text" id="domain" name="domain" maxlength="50">
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" id="cancelAddServer" class="cancel-btn">Отменить</button>
                            <button type="submit" id="createServer" class="create-btn">Создать</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Подключение платформы -->
        <?php include ROOT_PATH . '/include/error.php'; ?>
        <?php include ROOT_PATH . '/include/footer.php'; ?>
        <!-- Скрипты платформы -->
        <script src="/js/error.js"></script>
        <!-- Скрипты модуля -->
        <script src="js/Server_hardware/server_hardware.js"></script>
        <script src="js/Server_hardware/ServerCard.js"></script>
        <script src="js/Server_hardware/global_check.js"></script>
        <script src="js/Server_hardware/createServer.js"></script>
    </body>
</html>