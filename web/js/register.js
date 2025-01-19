$(document).ready(function() {
    // Отправка формы через AJAX
    $('#registrationForm').on('submit', function(event) {
        event.preventDefault(); // Отменяем стандартную отправку формы

        $.ajax({
            url: $(this).attr('action'),
            method: $(this).attr('method'),
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showNotification(response.message, 'success');
                } else {
                    showNotification(response.message, 'error');
                }
            },
            error: function() {
                showNotification('Ошибка при отправке запроса.', 'error');
            }
        });
    });

    // Функция для отображения уведомления
    function showNotification(message, type) {
        const notification = $('#notification');
        notification.text(message)
                   .removeClass('success error')
                   .addClass(type)
                   .fadeIn();

        // Скрыть уведомление через 5 секунд
        setTimeout(function() {
            notification.fadeOut();
        }, 5000);
    }
});