// back.js - Обработчик кнопки "Назад"

document.addEventListener('DOMContentLoaded', function() {
    // Находим кнопку "Назад" по ID
    const backButton = document.getElementById('backButton');
    
    // Если кнопка найдена, добавляем обработчик события
    if (backButton) {
        backButton.addEventListener('click', function(e) {
            // Предотвращаем стандартное поведение (если кнопка в форме)
            e.preventDefault();
            
            // Перенаправляем пользователя на страницу users.php
            window.location.href = '/modules/users/users.php';
            
            // Альтернативный вариант с использованием истории браузера:
            // history.back(); // Возврат на предыдущую страницу
            // или
            // window.history.go(-1); // Аналогично history.back()
        });
        
        // Добавляем всплывающую подсказку при наведении
        backButton.title = "Вернуться к списку пользователей";
        
        // Для лучшего UX можно добавить анимацию при наведении
        backButton.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05)';
            this.style.transition = 'transform 0.2s ease';
        });
        
        backButton.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    } else {
        console.warn('Кнопка "Назад" не найдена на странице');
    }
});