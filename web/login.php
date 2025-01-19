<?php
session_start();

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
			href="/css/login.css"
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
		<!-- Основной контент -->
		<main class="authorization ">
				<h2>Авторизация</h2>
				<form action="authorization.php" method="POST">
					<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
					<!-- Поле для логина -->
					<div class="input-group">
						<label for="login">Логин:</label>
						<input type="text" id="login" name="login" placeholder="Введите логин" required>
					</div>
					<!-- Поле для пароля -->
					<div class="input-group">
						<label for="password">Пароль:</label>
						<input type="password" id="password" name="password" placeholder="Введите пароль" required>
					</div>	
					<?php if (!empty($error_message)): ?>
						<div class="error-message">
							<?php echo $error_message; ?>
						</div>
					<?php endif; ?>
					<!-- Кнопка отправки -->					
					<input type="submit" value="Войти">
				</form>
		</main>
		<?php include 'footer.html'; ?>
		<!-- Подключаем внешний скрипт -->
		<script src="js/input_err.js"></script>
	</body>
</html>