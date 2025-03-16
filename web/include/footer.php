<?php
    // Проверка существования файла конфигурации
    if (!defined('CONFIG_PATH')) {
        logger("ERROR", "Переменная CONFIG_PATH не определена.");
        $currentVersion = '0.0.0'; // Используем значение по умолчанию
    } else {
        if (file_exists(CONFIG_PATH)) {
            // Чтение содержимого файла
            $configJson = file_get_contents(CONFIG_PATH);
            if ($configJson === false) {
                logger("ERROR", "Ошибка при чтении файла конфигурации: " . CONFIG_PATH);
                $currentVersion = '0.0.0'; // Используем значение по умолчанию
            } else {
                // Декодирование JSON
                $config = json_decode($configJson, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $currentVersion = $config['version']['current_version'] ?? '0.0.0'; // Если версия не найдена, используем значение по умолчанию
                } else {
                    logger("ERROR", "Ошибка при декодировании JSON: " . json_last_error_msg());
                    $currentVersion = '0.0.0';
                }
            }
        } else {
            logger("ERROR", "Файл конфигурации не найден: " . CONFIG_PATH);
            $currentVersion = '0.0.0'; // Используем значение по умолчанию
        }
    }
    logger("INFO", "Футер загружен успешно");
?>

<!-- Подвал -->
<footer class="footer">
    <div class="version">Version: <?php echo htmlspecialchars($currentVersion); ?></div>
    <div class="version">Apache License Version 2.0, 2025</div>
</footer>