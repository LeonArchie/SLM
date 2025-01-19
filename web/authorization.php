<?php
	// Начало сессии
	session_start();
	if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
		$error_message = "Ошибка безопасности: неверный CSRF-токен.";
		header("Location: login.php?error=" . urlencode($error_message)); // Возвращаем на страницу авторизации с сообщением об ошибке
		exit();
	}	
	// Подключение к базе данных
	require_once 'db_connect.php'; // Файл с подключением к PostgreSQL
	// Проверка, была ли отправлена форма
	if ($_SERVER["REQUEST_METHOD"] == "POST") {		
		// Получение данных из формы
		$login = trim($_POST['login']); // Удаляем лишние пробелы
		$password = trim($_POST['password']);
		if (empty($login) || empty($password)) {
			$error_message = "Логин и пароль обязательны для заполнения!";
			header("Location: login.php?error=" . urlencode($error_message));
			exit();
		}
		try {				
			// Поиск пользователя в базе данных
			$sql = "SELECT userid, userlogin, password_hash, roleid, usernames FROM users WHERE userlogin = :userlogin";
			$stmt = $pdo->prepare($sql);
			$stmt->execute(['userlogin' => $login]);
			$user = $stmt->fetch();
			// Проверка пароля
			if ($user && password_verify($password, $user['password_hash'])) {
				// Успешная авторизация
				// Генерация уникального ID сессии
				$session_id = session_id();
				// Сохранение данных пользователя в сессии
				$_SESSION['username'] = htmlspecialchars($user['usernames']);
				$_SESSION['userid'] = $user['userid'];
				$_SESSION['session_id'] = $session_id;
				$_SESSION['roleid'] = $user['roleid'];
				// Запись ID сессии в куки
				setcookie("session_id", $session_id, time() + 3600, "/"); // Куки действует 1 час
				// Перенаправление на защищенную страницу
				header("Location: dashboard.php");
				exit();
			} 
			else {
				// Неудачная авторизация
				sleep(2);
				$error_message = "Неверный логин или пароль!";
				header("Location: login.php?error=" . urlencode($error_message)); // Возвращаем на страницу авторизации с сообщением об ошибке
				exit();
			}
		} 
		catch (PDOException $e) {
			error_log("Ошибка выполнения запроса: " . $e->getMessage());
			$error_message = "Произошла ошибка. Пожалуйста, попробуйте позже.";
			header("Location: login.php?error=" . urlencode($error_message)); // Возвращаем на страницу авторизации с сообщением об ошибке
			exit();
		}
	} 
	else {
		// Если форма не была отправлена, перенаправляем на страницу авторизации
		header("Location: login.php");
		exit();
	}
?>