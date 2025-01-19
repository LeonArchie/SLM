<?php
// Начало сессии
session_start();

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