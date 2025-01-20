<?php
	require_once 'include/function.php';
	logger(); // Логирование
	startSessionIfNotStarted(); // Запуск сессии
	checkAuth(); // Проверка авторизации
	csrf_token(); // Генерация CSRF-токена
?>
<!DOCTYPE html> 											
<html lang="ru">
	<head>
		<!--Заголовок-->
		<title>SLM</title>	
		<!--Кодировка-->
		<meta charset="utf-8">							
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
			href="/css/navbar.css"
		/>
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
		<?php include 'include/header.html'; ?>
		<?php include 'include/navbar.html'; ?>
		<main>
			<h1>Добро пожаловать, <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Гость'; ?>!</h1>!</h1>
			<p>Просмотреть все аккаунты</p>
			<p> Страница в разработке</p>
			<p>ID вашей сессии: <?php echo htmlspecialchars($_SESSION['session_id']); ?></p>
		</main>
		<?php include 'include/footer.html'; ?>	
	</body>
</html>