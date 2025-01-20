<?php
    session_start();
    
    // Удаление данных сессии
    session_unset();
    session_destroy();

    // Удаление куки
    setcookie("session_id", "", time() - 3600, "/");

    // Перенаправление на страницу авторизации
    header("Location: /login.php");
    exit();
?>