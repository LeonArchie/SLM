document.addEventListener("DOMContentLoaded", function () {
    const errorMessage = "<?php echo $error_message; ?>";
    if (errorMessage) {
        const errorWindow = document.getElementById('error-window');
        const progressBar = document.getElementById('progress-bar');

        // Показываем окно ошибки
        errorWindow.style.display = 'flex';

        // Время показа в миллисекундах
        const totalTime = 10000;
        let timeLeft = totalTime;

        // Обновляем ширину полосы прогресса каждые 100мс
        const interval = setInterval(function () {
            const progressWidth = (timeLeft / totalTime) * 100;
            progressBar.style.width = `${progressWidth}%`;

            timeLeft -= 100;

            if (timeLeft <= 0) {
                clearInterval(interval);
                errorWindow.style.display = 'none';
            }
        }, 100);

        // Устанавливаем текст ошибки
        document.getElementById('error-message').textContent = errorMessage;
    }
});