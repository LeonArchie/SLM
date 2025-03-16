<?php
    // Проверка существования файла конфигурации
    if (!defined('CONFIG_PATH')) {
        // Логируем ошибку, если переменная CONFIG_PATH не определена
        logger("ERROR", "Переменная CONFIG_PATH не определена.");
        $currentVersion = '0.0.0'; // Используем значение по умолчанию
    } else {
        // Проверяем, существует ли файл конфигурации
        if (file_exists(CONFIG_PATH)) {
            // Чтение содержимого файла конфигурации
            $configJson = file_get_contents(CONFIG_PATH);
            if ($configJson === false) {
                // Логируем ошибку, если не удалось прочитать файл
                logger("ERROR", "Ошибка при чтении файла конфигурации: " . CONFIG_PATH);
                $currentVersion = '0.0.0'; // Используем значение по умолчанию
            } else {
                // Декодирование JSON
                $config = json_decode($configJson, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    // Получаем текущую версию из конфигурации, если она существует, иначе используем значение по умолчанию
                    $currentVersion = $config['version']['current_version'] ?? '0.0.0';
                } else {
                    // Логируем ошибку, если не удалось декодировать JSON
                    logger("ERROR", "Ошибка при декодировании JSON: " . json_last_error_msg());
                    $currentVersion = '0.0.0';
                }
            }
        } else {
            // Логируем ошибку, если файл конфигурации не найден
            logger("ERROR", "Файл конфигурации не найден: " . CONFIG_PATH);
            $currentVersion = '0.0.0'; // Используем значение по умолчанию
        }
    }

    // Логируем успешное завершение загрузки футера
    logger("INFO", "Футер загружен успешно");
?>