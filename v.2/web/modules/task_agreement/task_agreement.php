<?php
    // Уникальный идентификатор страницы для проверки привилегий
    $privileges_page = '8f4e3aa7-b05b-455e-b7dc-ca599953b9ad';


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
    logger("DEBUG", "tack_agreement.php успешно инициализирован.");
?>
<!DOCTYPE html>
<html lang="ru">
    <head>
        <?php include ROOT_PATH . '/platform/include/visible/all_head.html'; ?>
        <link rel="stylesheet" href="/platform/include/css/navbar.css"/>
        <link rel="stylesheet" href="/platform/include/css/error.css"/>
        <link rel="stylesheet" href="css/task_agreement.css"/>
        <title>ЕОС - Согласование исполнения сценариев</title>
    </head>
    <body>
        <?php include ROOT_PATH . '/platform/include/visible/eos_header.html'; ?>
        <?php include ROOT_PATH .'/platform/include/visible/navbar.php'; ?>
        <main>
            <div class="form-container">
            <h1 class="main-header"> <span class="servers-icon"></span> Согласование исполнения сценариев</h1>
                <div class="button-bar">
                    <div class="button-group">
                        <button id="ViewTask" disabled>Просмотреть конфигурацию сценария</button>
                        <button id="Pozitive" disabled>Согласовать</button>
                        <button id="Negative" disabled>Отклонить</button>
                        <button id="refreshButton" onclick="location.reload()">Обновить</button>
                    </div>
                    <!-- Добавляем поле поиска -->
                    <div class="search-container">
                        <input type="text" id="serverSearch" placeholder="Поиск согласования..." class="search-input">
                    </div>
                    <input type="hidden" name="userid" value="<?php echo $_SESSION['userid']; ?>">
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
                                <th>ID задачи</th>
                                <th>Наименование</th>
                                <th>Статус</th>
                                <th>Заказчик</th>
                                <th>Конфигурация</th>
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