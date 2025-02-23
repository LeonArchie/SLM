<?php
require_once 'include/init.php';
logger("INFO", "Начало выполнения скрипта info.php.");

// Проверка, есть ли сообщение об ошибке
$error_message = "";
if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
    logger("ERROR", "Получено сообщение об ошибке: " . $error_message);
}
?>
<!DOCTYPE html> 											
<html lang="ru">
	<head>
		<?php include 'include/all_head.html'; ?>	
	</head>
	<body>
		<?php include 'include/header.html'; ?>
		<?php include 'include/navbar.html'; ?>
		<main>
            <?php phpinfo(); ?>
		</main>
		<?php include 'include/footer.php'; ?>
	</body>
</html>