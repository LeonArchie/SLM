<?php
// Путь к JSON-файлу конфигурации
$configFilePath = '/config/config.json';

// Логирование начала загрузки футера
logger("INFO", "Начало загрузки футера.");

// Проверка существования файла конфигурации
if (file_exists($configFilePath)) {
    logger("INFO", "Файл конфигурации найден: $configFilePath");

    // Чтение содержимого файла
    $configJson = file_get_contents($configFilePath);
    if ($configJson === false) {
        logger("ERROR", "Ошибка при чтении файла конфигурации: $configFilePath");
        $currentVersion = '0.0.0'; // Используем значение по умолчанию
    } else {
        logger("INFO", "Файл конфигурации успешно прочитан.");

        // Декодирование JSON
        $config = json_decode($configJson, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            logger("INFO", "JSON успешно декодирован.");

            // Получение текущей версии
            $currentVersion = $config['version']['current_version'] ?? '0.0.0'; // Если версия не найдена, используем значение по умолчанию
            logger("INFO", "Текущая версия загружена: $currentVersion");
        } else {
            logger("ERROR", "Ошибка при декодировании JSON: " . json_last_error_msg());
            $currentVersion = '0.0.0'; // Используем значение по умолчанию
        }
    }
} else {
    logger("ERROR", "Файл конфигурации не найден: $configFilePath");
    $currentVersion = '0.0.0'; // Используем значение по умолчанию
}

// Логирование завершения загрузки футера
logger("INFO", "Загрузка футера завершена. Текущая версия: $currentVersion");
?>

<!-- Подвал -->
<footer class="footer">
    <div class="version">Version: <?php echo htmlspecialchars($currentVersion); ?></div>
    <div class="version">Apache License Version 2.0, 2025</div>
</footer>