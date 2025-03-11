<?php
    require_once __DIR__ . '/../include/function.php';
 
    startSessionIfNotStarted();

    // Логирование информации о пользователе перед выходом
    if (isset($_SESSION['username'])) {
        logger("INFO", "Пользователь " . $_SESSION['username'] . " начал процесс выхода из системы.");
    }

    // Удаление CSRF-токена из сессии
    if (isset($_SESSION['csrf_token'])) {
        unset($_SESSION['csrf_token']);
    }

    // Удаление CSRF-токена из куки
    if (isset($_COOKIE['csrf_token'])) {
        setcookie("csrf_token", "", time() - 3600, "/");
    }

    // Удаление данных сессии
    session_unset();

    // Уничтожение сессии
    if (!session_destroy()) {
        logger("ERROR", "Не удалось уничтожить сессию.");
    }

    // Удаление куки session_id
    if (isset($_COOKIE['session_id'])) {
        if (!setcookie("session_id", "", time() - 3600, "/")) {
            logger("ERROR", "Не удалось удалить куку session_id.");
        } 
    }

    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'неизвестный IP';
    logger("INFO", "Перенаправление на страницу авторизации. IP пользователя: " . $ipAddress);

    header("Location: /../login.php");
    exit();
?>