<?php
	// Параметры подключения к базе данных
	$host = '192.168.3.4'; // Хост базы данных
	$port = '5432'; // Порт PostgreSQL (по умолчанию 5432)
	$dbname = 'RLM-PSI'; // Имя базы данных
	$user = 'RLM_PSI_USER'; // Имя пользователя
	$password = "vbn\$grjemd#mf!"; // Пароль пользователя
	// Строка подключения к PostgreSQL
	$dsn = "pgsql:host=$host;port=$port;dbname=$dbname;options='--client_encoding=UTF8'";
	try {
		// Создание подключения к базе данных
		$pdo = new PDO($dsn, $user, $password);
		// Установка атрибутов PDO
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Режим ошибок: выбрасывать исключения
		$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // Режим выборки данных: ассоциативный массив
	} 
	catch (PDOException $e) {
		error_log("Ошибка подключения к базе данных: " . $e->getMessage());
		die("Произошла ошибка. Пожалуйста, попробуйте позже.");
	}
?>