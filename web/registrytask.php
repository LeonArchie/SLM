<?php
	require_once 'include/function.php';
		logger(); // Логирование
		startSessionIfNotStarted(); // Запуск сессии
		checkAuth(); // Проверка авторизации
		csrf_token(); // Генерация CSRF-токена
	// Проверка, есть ли сообщение об ошибке
	$error_message = "";
	if (isset($_GET['error'])) {
		$error_message = htmlspecialchars($_GET['error']);
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
			<h1>Добро пожаловать, <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Гость'; ?>!</h1>
			<p>Реестр заявок</p>
			<p> Страница в разработке</p>
			<p>ID вашей сессии: <?php echo htmlspecialchars($_SESSION['session_id']); ?></p>
		</main>
		<?php include 'include/footer.html'; ?>	
	</body>
</html>