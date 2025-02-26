<?php
    $modules = '356a5297-1587-4d79-8f81-b3e1c7e21a73';
    $pages = '62583be6-530e-42b2-b490-b0b082d47e66';

	$file_path = __DIR__ . '/platform.php';
    if (!file_exists($file_path)) {
        // Если файл не существует, переходим на страницу 503.php
        header("Location: /err/403.html");
        exit();
    }
	require_once $file_path;
    
    logger("DEBUG", "Фрод готов к запуску");
    FROD($modules);

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
        <?php include ROOT_PATH . '/include/all_head.html'; ?>
        <!-- Подключение стилей -->
        <link rel="stylesheet" href="/css/error.css"/>
        <link rel="stylesheet" href="/css/navbar.css"/>
        <link rel="stylesheet" href="/css/all_accounts.css"/>
    </head>
    <body>
        <?php include ROOT_PATH . '/include/eos_header.html'; ?>
        <?php include ROOT_PATH .'/include/navbar.php'; ?>
        <main>


        </main>
        <?php include ROOT_PATH . '/include/error.php'; ?>
        <?php include ROOT_PATH . '/include/footer.php'; ?>
    </body>
</html>