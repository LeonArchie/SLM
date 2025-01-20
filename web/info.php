<?php
	require_once 'include/function.php';
		logger(); // Логирование
		startSessionIfNotStarted(); // Запуск сессии
		checkAuth(); // Проверка авторизации
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
		<?php include 'include/footer.html'; ?>	
	</body>
</html>