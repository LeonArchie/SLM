document.addEventListener('DOMContentLoaded', function() {
    // Находим кнопку по ID
    const globalCheckButton = document.getElementById('GlobalCheck');
    
    // Добавляем обработчик события клика
    if (globalCheckButton) {
        globalCheckButton.addEventListener('click', function() {
            // Перенаправляем пользователя на Server_global_check.php
            window.location.href = 'Server_global_check.php';
        });
    } else {
        console.error('Кнопка с ID "GlobalCheck" не найдена на странице.');
    }
});