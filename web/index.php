<?php
	require_once 'include/function.php';
        logger(); // Логирование
        startSessionIfNotStarted(); // Запуск сессии
    // Проверка, авторизован ли пользователь
    if (isset($_SESSION['session_id']) && isset($_SESSION['username'])) {
        // Пользователь авторизован, перенаправляем на dashboard.php
        header("Location: dashboard.php");
        exit();
    } else {
        // Пользователь не авторизован, перенаправляем на login.php
        header("Location: login.php");
        exit();
    }
?>