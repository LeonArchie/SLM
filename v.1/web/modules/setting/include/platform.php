<?php
        if (!defined('ROOT_PATH')) {
            define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
        }

        if (!defined('INIT_PLATFORM')) {
            define('INIT_PLATFORM',  ROOT_PATH .'/include/function.php');
        }

        if (!defined('TEMPLATE_PRIVILEGES')) {
            define('TEMPLATE_PRIVILEGES',  ROOT_PATH .'/config/template.json');
        }


    // Инициализация функций
    $file_path = INIT_PLATFORM;
    if (!file_exists($file_path)) {
        header("Location: /err/50x.html");
        exit();
    }
    
    require_once $file_path;
?>