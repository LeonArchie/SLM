<?php
    // Инициализация вызвываемых функции
    $file_path = '/include/init.php';
    if (!file_exists($file_path)) {
        // Если файл не существует, переходим на страницу 503.php
        header("Location: /err/50x.html");
        exit();
    }
    
	// Подключаем файл, так как он существует
    require_once $file_path;
	logger("INFO", "Начало выполнения скрипта info.php.");

	// Проверка, есть ли сообщение об ошибке
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
		<link rel="stylesheet" href="css/error.css"/>
	</head>
	<body>
		<?php include 'include/header.html'; ?>
		<?php include 'include/navbar.html'; ?>
		<main>
            <?php phpinfo(); ?>
		</main>
		<?php include 'include/error.php'; ?>
		<?php include 'include/footer.php'; ?>
	</body>
</html>