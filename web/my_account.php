<?php
require_once 'include/function.php';

// Логирование начала выполнения скрипта
logger("INFO", "Начало выполнения скрипта all_accounts.php.");

// Запуск сессии
startSessionIfNotStarted();
logger("INFO", "Сессия успешно запущена. ID сессии: " . session_id());

// Проверка авторизации
checkAuth();
logger("INFO", "Пользователь авторизован. Username: " . $_SESSION['username']);

// Генерация CSRF-токена
csrf_token();
logger("INFO", "CSRF-токен сгенерирован.");

// Проверка, есть ли сообщение об ошибке
$error_message = "";
if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
    logger("ERROR", "Получено сообщение об ошибке: " . $error_message);
}
?>
<!DO
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
			<p>Мой аккаунт</p>
			<p> Страница в разработке</p>
			<p>ID вашей сессии: <?php echo htmlspecialchars($_SESSION['session_id']); ?></p>
		</main>
		<?php include 'include/footer.html'; ?>	
	</body>
</html>