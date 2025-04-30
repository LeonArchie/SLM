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
        <link rel="stylesheet" href="css/view_task.css"/>
        <title>ЕОС - Выбор сценариев</title>
    </head>
    <body>
        <?php include ROOT_PATH . '/platform/include/visible/eos_header.html'; ?>
        <?php include ROOT_PATH .'/platform/include/visible/navbar.php'; ?>
        <main>
            <div class="form-container">
            <h1 class="main-header"> <span class="servers-icon"></span> Выбор сценария</h1>
                <div class="button-bar">
                    <div class="button-group">
                        <?php 
                            $privileges_button = '8bce93fa-ef63-4957-a28e-d8d839cf29b2';
                            if (checkPrivilege( $privileges_button)): ?>
                            <button id="CreateTask">Создать сценарий</button>
                            <button id="lockTask">Заблокировать сценарий</button>
                            <button id="DelTask">Удалить сценарий</button>
                        <?php endif; ?>

                        <?php 
                            $privileges_button = 'c91f52c7-f797-43bf-b33c-5a171be5360e';
                            if (checkPrivilege( $privileges_button)): ?>
                            <button id="SettingTask">Настроить сценарий</button>
                        <?php endif; ?>
                    </div>
                    <!-- Добавляем поле поиска -->
                    <div class="search-container">
                        <input type="text" id="serverSearch" placeholder="Поиск сценария..." class="search-input">
                    </div>
                    <input type="hidden" name="userid" value="<?php echo $_SESSION['userid']; ?>">
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Изображение</th>
                                <th>Название сценария</th>
                                <th>Краткое описание сценария</th>
                                <th>Активен</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                foreach ($servers as $server):
                                    $namse = htmlspecialchars($server['namse'] ?? 'Не указано');
                                    $name = htmlspecialchars($server['name'] ?? 'Не указано');
                                    $stand = htmlspecialchars($server['stand'] ?? 'Не указан');
                                    $status = htmlspecialchars($server['status'] ?? 'Неизвестен');
                            ?>
                            <tr>
                                <td class="name-cell">
                                    <a href="#" onclick="event.preventDefault(); redirectToServerCard(<?= json_encode($servId) ?>);">
                                        <?= $name ?>
                                    </a>
                                </td>
                                <td><?= $stand ?></td>
                                <td><?= $status ?></td>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php include ROOT_PATH . '/platform/include/visible/loading.html'; ?>  
        </main>      
        
        <?php include ROOT_PATH . '/platform/include/visible/error.php'; ?>
        <?php include ROOT_PATH . '/platform/include/visible/footer.php'; ?>
        
        <script src="/platform/include/js/error.js"></script>
    </body>
</html>