<?php

    // Определяем корень веб-сервера
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
    }

    logger("INFO", "Футер начал загрузку");

    include ROOT_PATH . "/platform/include/api/getVersionFromApi.php";

    try {
        $currentVersion = getVersionFromApi();
    } catch (Throwable $e) {
        logger("ERROR", "Failed to get version: " . $e->getMessage());
        $currentVersion = '0.0.0.0';
    }

    // Логируем успешное завершение загрузки футера
    logger("INFO", "Футер загружен успешно. Версия: " . $currentVersion);
?>

<!-- Подвал -->
<footer class="footer">
    <div class="version">Version: <?php echo htmlspecialchars($currentVersion); ?></div>
    <div class="version">Apache License Version 2.0, 2025</div>
</footer>