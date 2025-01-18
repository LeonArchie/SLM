 <?php
	//session_start();
	// Проверка, авторизован ли пользователь
	//if (!isset($_SESSION['user']) || !isset($_COOKIE['session_id']) || $_COOKIE['session_id'] !== session_id()) {
		// Если пользователь не авторизован или куки не совпадают, перенаправляем на страницу авторизации
		//header("Location: logout.php");
	//exit();
	//}
?>
<!DOCTYPE html> 											
<html>
	<head>
		<!--Заголовок-->
		<title>SLM</title>	
		<!--Кодировка-->
		<meta charset="utf-8">							
		 <!-- Адаптивность -->
		<meta											
			name="viewport"
			content="width=device-width, initial-scale=1.0"
		/>
		<!--Ключевые слова-->
		<meta
			name="description"
			content="Управление жизненным циклом серверов и приложений"
		/>
		<!--Минус роботы-->
		<meta 
			name="robots"
			content="noindex, nofollow" 
		/>
		<!-- Подключение стилей -->
		<link 
			rel="stylesheet" 
			href="/css/all.css"
		/>
		<!-- Фавикон -->
		<link
			rel="icon"
			sizes="16x16 32x32 48x48"
			type="image/png"
			href="/img/icon.png"
		/>
	</head>
	<body>
		<?php include 'header.html'; ?>
		<?php include 'menu.html'; ?>
		<main>
			<h1>Добро пожаловать, <?php echo htmlspecialchars($_SESSION['user']); ?>!</h1>
			<p>ID вашей сессии: <?php echo htmlspecialchars($_SESSION['session_id']); ?></p>
			<a href="logout.php">Выйти</a>
		</main>
		<?php include 'footer.html'; ?>	
	</body>
</html>