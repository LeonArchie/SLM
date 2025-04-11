<?php
    // Путь к файлу с функциями
    $file_path = 'include/function.php';

    // Проверка существования файла
    if (!file_exists($file_path)) {
        // Если файл не существует, перенаправляем на страницу ошибки 503
        header("Location: /err/50x.html");
        exit(); // Завершаем выполнение скрипта
    }
    
    // Подключаем файл с функциями
    require_once $file_path;

    // Запуск сессии, если она еще не была запущена
    startSessionIfNotStarted();

    // Логирование информации о пользователе перед выходом
    if (isset($_SESSION['username'])) {
        // Логируем информацию о том, что пользователь начал процесс выхода
        logger("INFO", "Пользователь " . $_SESSION['username'] . " начал процесс выхода из системы.");
        logger("INFO", "Пользователь " . $_SESSION['username'] . " запустил процесс выхода из системы.");
    }

    // Удаление access_token из сессии
    if (isset($_SESSION['access_token'])) {
        unset($_SESSION['access_token']);
    }

    // Удаление refresh_token из сессии
    if (isset($_SESSION['refresh_token'])) {
        unset($_SESSION['refresh_token']);
    }

    // Удаление userid из сессии
    if (isset($_SESSION['userid'])) {
        unset($_SESSION['userid']);
    }

    // Удаление refresh_token из сессии
    if (isset($_SESSION['username'])) {
        unset($_SESSION['username']);
    }

    // Очистка всех данных сессии
    session_unset();

    // Уничтожение сессии
    if (!session_destroy()) {
        // Логируем ошибку, если не удалось уничтожить сессию
        logger("ERROR", "Не удалось уничтожить сессию.");
    }

    // Получение IP-адреса пользователя для логирования
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'неизвестный IP';
    // Логируем информацию о перенаправлении на страницу авторизации
    logger("INFO", "Перенаправление на страницу авторизации. IP пользователя: " . $ipAddress);

    // Перенаправление пользователя на страницу авторизации
    header("Location: login.php");
    exit(); // Завершение выполнения скрипта
?>  
