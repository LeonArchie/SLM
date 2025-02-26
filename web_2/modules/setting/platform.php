<?php
        define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
        define('MODULES_PATH', ROOT_PATH . '/modules/setting');
        define('INIT_PLATFORM', ROOT_PATH . '/include/init.php');
        define('INIT_FUNCTION', ROOT_PATH . '/include/function.php');

    // Инициализация вызвываемых функции
    $file_path = INIT_PLATFORM;
    if (!file_exists($file_path)) {
        // Если файл не существует, переходим на страницу 503.php
        header("Location: /err/404.html");
        exit();
    }
    
    // Подключаем файл, так как он существует
    require_once $file_path;
?>