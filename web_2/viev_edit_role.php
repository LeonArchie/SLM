<?php
    $modules = 'da137713-83fe-4325-868f-14b967dbf17c';
    $pages = 'aeef82b7-5083-480e-a59e-507a083a16be';

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
    FROD($module, $pages);

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
        <link rel="stylesheet" href="css/error.css"/>
        <link rel="stylesheet" href="css/navbar.css"/>
        <link rel="stylesheet" href="css/all_accounts.css"/>
    </head>
    <body>
        <?php include 'include/eos_header.html'; ?>
        <?php include 'include/navbar.php'; ?>
        <main>
			<div class="message">
                <h1>Добро пожаловать, <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Гость'; ?>!</h1>
                <p>ТЕСТ ФРОДА</p>
                <p> Скоро мы добавим информацию для Вас</p>
            </div>
        </main>
        <?php include 'include/error.php'; ?>
        <?php include 'include/footer.php'; ?>
    </body>
</html>