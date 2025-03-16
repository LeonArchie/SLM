// Ожидаем полной загрузки DOM перед выполнением скрипта
document.addEventListener('DOMContentLoaded', () => {
    // Получаем форму, подложку и элемент загрузки
    const form = document.getElementById('authForm');
    const overlay = document.querySelector('.overlay'); // Подложка
    const loading = document.getElementById('loading');

    // Добавляем обработчик события отправки формы
    form.addEventListener('submit', async (event) => {
        event.preventDefault(); // Предотвращаем стандартную отправку формы

        try {
            // Показываем подложку с загрузочной анимацией
            loading.style.display = 'flex';

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

            // Проверяем успешность авторизации
            if (result.success) {
                // Если авторизация успешна, выполняем перенаправление
                if (result.redirect) {
                    window.location.href = result.redirect; // Перенаправление
                } else {
                    // Если перенаправление не указано, показываем ошибку
                    showErrorMessage('error', 'Ошибка', 'Ошибка 0011: Произошла ошибка при попытке перенаправления.', 5000);
                }
            } else {
                // Если возникла ошибка, показываем её через showErrorMessage
                const errorMessage = result.message || 'Ошибка 0012: Произошла неизвестная ошибка.';
                showErrorMessage('error', 'Ошибка', errorMessage, 5000);
            }
        } catch (error) {
            // Обработка ошибок сервера
            showErrorMessage('error', 'Ошибка', 'Ошибка 0013: Не удалось подключиться к серверу. Попробуйте позже.', 5000);
        } finally {
            // Скрываем подложку после завершения загрузки
            loading.style.display = 'none';
        }
    });
});

/**
 * Функция для отображения сообщений об ошибках, предупреждениях и успехах
 * @param {string} type - Тип сообщения ('error', 'warning', 'success')
 * @param {string} title - Заголовок сообщения
 * @param {string} message - Текст сообщения
 * @param {number} duration - Время отображения сообщения в миллисекундах
 */
function showErrorMessage(type, title, message, duration) {
    // Создаем элемент для сообщения
    const messageElement = document.createElement('div');
    messageElement.className = `message ${type}`;
    messageElement.innerHTML = `<strong>${title}</strong>: ${message}`;

    // Добавляем сообщение на страницу
    document.body.appendChild(messageElement);

    // Устанавливаем таймер для автоматического удаления сообщения
    setTimeout(() => {
        messageElement.remove();
    }, duration);
}