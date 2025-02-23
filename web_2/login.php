<?php
    // Инициализация вызвываемых функции
    $file_path = 'include/function.php';
    if (!file_exists($file_path)) {
        // Если файл не существует, переходим на страницу 503.php
        header("Location: /err/50x.html");
        exit();
    }
    
    // Подключаем файл, так как он существует
    require_once $file_path;
    
    // Логирование начала выполнения скрипта
    logger("INFO", "Начало выполнения скрипта login.php.");

    // Запуск сессии
    startSessionIfNotStarted();

    // Генерация CSRF-токена
    csrf_token();

    $error_message = "";
    if (isset($_GET['error'])) {
        $raw_error = $_GET['error']; // Сохраняем сырое значение
        $error_message = htmlspecialchars($raw_error, ENT_QUOTES, 'UTF-8');
        logger("DEBUG", "Сырое значение параметра error: " . $raw_error);
        logger("ERROR", "Получено сообщение об ошибке: " . $error_message);
    } else {
        logger("WARNING", "Параметр 'error' не передан.");
    }
?>
<!DOCTYPE html>
<html lang="ru">
    <head>
        <?php include 'include/all_head.html'; ?>
        <!-- Подключение стилей -->
        <link rel="stylesheet" href="css/login.css"/>
        <link rel="stylesheet" href="css/error.css"/>
    </head>
    <body>
        <?php include 'include/eos_header.html'; ?>
        <!-- Основной контент -->
        <main class="authorization">
            <h2>Авторизация</h2>
            <form id="authForm" action="back/authorization.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <!-- Поле для логина -->
                <div class="input-group">
                    <label for="login">Логин:</label>
                    <input type="text" id="login" name="login" placeholder="Введите логин" required>
                </div>
                <!-- Поле для пароля -->
                <div class="input-group">
                    <label for="password">Пароль:</label>
                    <input type="password" id="password" name="password" placeholder="Введите пароль" required>
                </div>
                <!-- Кнопка отправки -->
                <input type="submit" value="Войти">
            </form>
            <!-- Подложка для загрузочной анимации -->
            <div class="overlay" style="display: none;">
                <img src="img/loading.gif" alt="Загрузка" class="loading-image">
            </div>
        </main>
        <?php include 'include/error.php'; ?>
        <?php include 'include/footer.php'; ?>
        <!-- Подключаем внешний скрипт -->
        <script src="js/error.js"></script>
        <script src="js/login.js"></script>
    </body>
</html>