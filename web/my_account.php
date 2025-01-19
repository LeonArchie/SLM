<?php
	session_start();
	// Проверка, авторизован ли пользователь
	if (!isset($_SESSION['username']) || !isset($_COOKIE['session_id']) || $_COOKIE['session_id'] !== session_id()) {
		// Если пользователь не авторизован или куки не совпадают, перенаправляем на страницу авторизации
		header("Location: logout.php");
	exit();
	}
	// Генерация CSRF-токена, если он еще не создан
	if (empty($_SESSION['csrf_token'])) {
    	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
	}
	// Проверка, есть ли сообщение об ошибке
	$error_message = "";
	if (isset($_GET['error'])) {
    	$error_message = htmlspecialchars($_GET['error']);
	}
?>
<!DOCTYPE html> 											
<html lang="ru">
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
		<?php include 'header.html'; ?>
		<?php include 'navbar.html'; ?>
		<main>


		</main>
		<?php include 'footer.html'; ?>	
	</body>
</html>