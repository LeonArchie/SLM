<?php
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
    }

    if (!defined('INIT_PLATFORM')) {
        define('INIT_PLATFORM',  ROOT_PATH .'/platform/include/function.php');
    }

    if (!defined('CHECK_AUTH')) {
        define('CHECK_AUTH',  ROOT_PATH .'/platform/include/binding/check_auth.php');
    }    

    if (!defined('FROD')) {
        define('FROD',  ROOT_PATH .'/platform/include/binding/frod.php');
    }  

    // Инициализация функций
    $file_path = INIT_PLATFORM;
    if (!file_exists($file_path)) {
        header("Location: /err/50x.html");
        exit();
    }
    
    require_once $file_path;
?>