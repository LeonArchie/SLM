<?php
    // Уникальный идентификатор страницы для проверки привилегий
    $privileges_page = '16bdb437-08e7-4783-8945-73618eab30e7';


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


//Логика страницы


    include "/platform/include/binding/inital_error.php";

    // Логирование успешной инициализации страницы
    logger("DEBUG", "servers.php успешно инициализирован.");
?>
<!DOCTYPE html>
<html lang="ru">
    <head>
        <?php include ROOT_PATH . '/platform/include/visible/all_head.html'; ?>
        <link rel="stylesheet" href="/platform/include/css/navbar.css"/>
        <link rel="stylesheet" href="/platform/include/css/error.css"/>
        <link rel="stylesheet" href="css/servers.css"/>
        <link rel="stylesheet" href="css/modal.css"/>
        <title>ЕОС - Серверное оборудование</title>
    </head>
    <body>
        <?php include ROOT_PATH . '/platform/include/visible/eos_header.html'; ?>
        <?php include ROOT_PATH .'/platform/include/visible/navbar.php'; ?>
        <main>
            <div class="form-container">
            <h1 class="main-header"> <span class="servers-icon"></span> Серверное оборудование</h1>
                <div class="button-bar">
                    <div class="button-group">
                        <?php 
                            $privileges_button = '305903e5-0b9a-4439-a828-7774d261bebd';
                            if (checkPrivilege( $privileges_button)): ?>
                            <button id="AddServers">Добавить оборудование</button>
                        <?php endif; ?>

                        <?php 
                            $privileges_button = '20ad6598-a302-47f1-bc39-ff8d99e6002f';
                            if (checkPrivilege( $privileges_button)): ?>
                            <button id="GlobalCheck">Глобальная проверка конфиликтов</button>
                        <?php endif; ?>

                        <button id="VievCardServer" disabled>Просмотреть карточку оборудования</button>
                        <button id="refreshButton" onclick="location.reload()">Обновить</button>
                    </div>
                    <!-- Добавляем поле поиска -->
                    <div class="search-container">
                        <input type="text" id="serverSearch" placeholder="Поиск оборудования..." class="search-input">
                    </div>
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
            <?php include 'include/modal.php'; ?>
            <?php include ROOT_PATH . '/platform/include/visible/loading.html'; ?>  
        </main>      
        
        <?php include ROOT_PATH . '/platform/include/visible/error.php'; ?>
        <?php include ROOT_PATH . '/platform/include/visible/footer.php'; ?>
           
        <script src="/platform/include/js/error.js"></script>
        <script src="/platform/include/js/check_jwt.js"></script>
    </body>
</html>