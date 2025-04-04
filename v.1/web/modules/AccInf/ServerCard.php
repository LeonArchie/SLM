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
        <link rel="stylesheet" href="css/ServerCard.css"/>
    </head>
    <body>
        <!-- Подключение платформы -->
        <?php include ROOT_PATH . '/include/eos_header.html'; ?>
        <?php include ROOT_PATH .'/include/navbar.php'; ?>
        <main>
            <div class="form-container">
                <div class="button-bar">
                    <button id="EditCard">Редактировать</button>
                    <button id="SevaCard" disabled>Сохранить</button>
                    <button id="Back">Назад</button>
                    <button id="refreshButton" onclick="location.reload()">Обновить</button>
                    <input type="hidden" name="userid" value="<?php echo $_SESSION['userid']; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                </div>
                <div class="table-container">
                </div>
            </div>
        </main>
        <!-- Подключение платформы -->
        <?php include ROOT_PATH . '/include/error.php'; ?>
        <?php include ROOT_PATH . '/include/footer.php'; ?>
        <!-- Скрипты платформы -->
        <script src="/js/error.js"></script>
        <!-- Скрипты модуля -->
        <script src="js/ServerCard/back.js"></script>
    </body>
</html>