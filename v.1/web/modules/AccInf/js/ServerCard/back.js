document.addEventListener('DOMContentLoaded', function() {
    // Получаем кнопку "Назад" по ID
    const backButton = document.getElementById('Back');
    
    // Добавляем обработчик события click
    backButton.addEventListener('click', function() {
        // Перенаправляем пользователя на указанный URL
        window.location.href = '/modules/AccInf/Server_hardware.php';
    });
});