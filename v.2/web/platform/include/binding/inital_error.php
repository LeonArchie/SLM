<?php   
   // Инициализация переменной для хранения сообщения об ошибке
    $error_message = "";

    // Проверяем, передана ли ошибка через GET-параметр
    if (isset($_GET['error'])) {
        $raw_error = $_GET['error']; // Сохраняем сырое значение ошибки
        $error_message = htmlspecialchars($raw_error, ENT_QUOTES, 'UTF-8'); // Экранируем специальные символы для безопасности
    }
?>