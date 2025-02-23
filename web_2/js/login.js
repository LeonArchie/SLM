document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('authForm');
    const overlay = document.querySelector('.overlay'); // Подложка

    // Предотвращаем стандартную отправку формы
    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        try {
            // Показываем подложку с загрузочной анимацией
            overlay.classList.remove('hidden'); // Показываем подложку

            // Собираем данные формы
            const formData = new FormData(form);

            // Отправляем данные на сервер
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                credentials: 'include', // Важно для передачи кук
            });

            // Получаем ответ от сервера
            const result = await response.json();

            if (result.success) {
                // Если авторизация успешна, выполняем перенаправление
                if (result.redirect) {
                    console.log('Redirecting to:', result.redirect);
                    window.location.href = result.redirect; // Перенаправление
                } else {
                    console.error('Ошибка: Поле "redirect" отсутствует в ответе сервера.');
                    showErrorMessage('Произошла ошибка при попытке перенаправления.');
                }
            } else {
                // Если возникла ошибка, показываем её через showErrorMessage
                const errorMessage = result.message || 'Произошла неизвестная ошибка.';
                showErrorMessage(errorMessage);
            }
        } catch (error) {
            // Обработка ошибок сети или сервера
            console.error('Network error:', error);
            showErrorMessage('Не удалось подключиться к серверу. Попробуйте позже.');
        } finally {
            // Скрываем подложку после завершения загрузки
            overlay.classList.add('hidden'); // Скрываем подложку
        }
    });
});