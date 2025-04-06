<?php

logger("INFO", "Футер начал загрузку");

include "/../api/getVersionFromApi.php";

// Получаем версию через API с обработкой исключений
try {
    $currentVersion = getVersionFromApi();
} catch (Exception $e) {
    logger("ERROR", "Failed to get version: " . $e->getMessage());
    $currentVersion = '0.0.0.0'; // Возвращаем 4 цифры при ошибке
}

// Логируем успешное завершение загрузки футера
logger("INFO", "Футер загружен успешно. Версия: " . $currentVersion);
?>

<!-- Подвал -->
<footer class="footer">
    <div class="version">Version: <?php echo htmlspecialchars($currentVersion); ?></div>
    <div class="version">Apache License Version 2.0, 2025</div>
</footer>