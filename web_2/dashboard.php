<?php
    // Инициализация вызвываемых функции
    $file_path = 'include/init.php';
    if (!file_exists($file_path)) {
        // Если файл не существует, переходим на страницу 503.php
        header("Location: /err/50x.html");
        exit();
    }
    
    // Подключаем файл, так как он существует
    require_once $file_path;
    
    logger("DEBUG", "Фрод готов к запуску");
    frod('3ad33b50-360f-4c8a-b691-aca388b48a8b');

    // Логирование начала выполнения скрипта
    logger("INFO", "Начало выполнения скрипта dashboard.php.");

    // Проверка, есть ли сообщение об ошибке
    $error_message = "";
    if (isset($_GET['error'])) {
        $raw_error = $_GET['error']; // Сохраняем сырое значение
        $error_message = htmlspecialchars($raw_error, ENT_QUOTES, 'UTF-8');
        logger("DEBUG", "Сырое значение параметра error: " . $raw_error);
        logger("ERROR", "Получено сообщение об ошибке: " . $error_message);
    } else {
        logger("INFO", "Параметр 'error' не передан.");
    }
?>
<!DOCTYPE html>
<html lang="ru">
    <head>
        <?php include 'include/all_head.html'; ?>
        <!-- Подключение стилей -->
        <link rel="stylesheet" href="css/login.css"/>
        <link rel="stylesheet" href="css/error.css"/>
        <link rel="stylesheet" href="css/navbar.css"/>
        <link rel="stylesheet" href="css/dashboard.css"/>
    </head>
    <body>
        <?php include 'include/eos_header.html'; ?>
        <?php include 'include/navbar.php'; ?>
        <main>
            <div class="message">
                <h1>Добро пожаловать, <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Гость'; ?>!</h1>
                <p>Единое окно сотрудника в разработке</p>
                <p> Скоро мы добавим информацию для Вас</p>
            </div>
            <?php include 'include/loading.html'; ?>
        </main>
        <?php include 'include/error.php'; ?>
        <?php include 'include/footer.php'; ?>
        <!-- Подключаем внешний скрипт -->
        <script src="js/error.js"></script>
    </body>
    
</html>