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

    // Получаем список скриптов пользователя через API
    $scripts = [];
    if (isset($_SESSION['access_token']) && isset($_SESSION['userid'])) {
        // Автоматически определяем базовый URL с портом 5000
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        
        // Удаляем стандартный порт если он есть в HTTP_HOST
        $host = str_replace([':80',':443'], '', $host);

        $apiBaseUrl = "{$protocol}://{$host}:5000";
        $api_url = "{$apiBaseUrl}/privileges/scripts/user_view";
        
        $post_data = json_encode([
            'access_token' => $_SESSION['access_token'],
            'user_id' => $_SESSION['userid']
        ]);

        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200) {
            $data = json_decode($response, true);
            if ($data['status'] === 'success') {
                $scripts = $data['scripts'];
            }
        }
    }

    include "/platform/include/binding/inital_error.php";

    // Логирование успешной инициализации страницы
    logger("DEBUG", "task_start.php успешно инициализирован.");
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
                            if (checkPrivilege($privileges_button)): ?>
                            <button id="CreateTask">Создать сценарий</button>
                            <button id="lockTask">Заблокировать сценарий</button>
                            <button id="DelTask">Удалить сценарий</button>
                        <?php endif; ?>

                        <?php 
                            $privileges_button = 'c91f52c7-f797-43bf-b33c-5a171be5360e';
                            if (checkPrivilege($privileges_button)): ?>
                            <button id="SettingTask">Настроить сценарий</button>
                        <?php endif; ?>
                        <button id="refreshButton" onclick="location.reload()">Обновить</button>
                    </div>
                    <!-- Добавляем поле поиска -->
                    <div class="search-container">
                        <input type="text" id="serverSearch" placeholder="Поиск сценария..." class="search-input">
                    </div>
                    <input type="hidden" name="userid" value="<?php echo $_SESSION['userid']; ?>">
                </div>
                <div class="table-container">
                    <div class="scripts-grid">
                        <?php if (empty($scripts)): ?>
                            <div class="empty-message">
                                Сценарии не найдены
                            </div>
                        <?php else: ?>
                            <?php foreach ($scripts as $script): ?>
                                <div class="script-card">
                                    <div class="script-id"><?= htmlspecialchars($script['guid_scripts'] ?? 'N/A') ?></div>
                                    <h3 class="script-title"><?= htmlspecialchars($script['name_scripts'] ?? 'Без названия') ?></h3>
                                    <div class="script-image">
                                        <img src="img/img.gif" alt="Изображение сценария">
                                    </div>
                                    <div class="script-description">
                                        <?= htmlspecialchars($script['description'] ?? 'Нет описания') ?>
                                    </div>
                                    <div class="script-tags">
                                        <?php if (isset($script['tag']) && is_array($script['tag'])): ?>
                                            <?php foreach ($script['tag'] as $tag): ?>
                                                <span class="tag"><?= htmlspecialchars($tag) ?></span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="tag">Нет тегов</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php include ROOT_PATH . '/platform/include/visible/loading.html'; ?>  
        </main>      
        <?php include ROOT_PATH . '/platform/include/visible/error.php'; ?>
        <?php include ROOT_PATH . '/platform/include/visible/footer.php'; ?>
        
        <script src="/platform/include/js/error.js"></script>
        <script src="/platform/include/js/check_jwt.js"></script>
        <script src="js/find_view_task.js"></script>
    </body>
</html>