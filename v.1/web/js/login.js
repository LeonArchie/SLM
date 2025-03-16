document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('authForm');
    const overlay = document.querySelector('.overlay'); // Подложка
    const loading = document.getElementById('loading');

    // Предотвращаем стандартную отправку формы
    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        try {
            // Показываем подложку с загрузочной анимацией
            loading.style.display = 'flex'

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
                    window.location.href = result.redirect; // Перенаправление
                } else {
                    showErrorMessage('Произошла ошибка при попытке перенаправления.');
                }
            } else {
                // Если возникла ошибка, показываем её через showErrorMessage
                const errorMessage = result.message || 'Произошла неизвестная ошибка.';
                showErrorMessage(errorMessage);
            }
        } catch (error) {
            // Обработка ошибок сервера
            showErrorMessage('Не удалось подключиться к серверу. Попробуйте позже.');
        } finally {
            // Скрываем подложку после завершения загрузки
            loading.style.display = 'none'
        }
    });
});