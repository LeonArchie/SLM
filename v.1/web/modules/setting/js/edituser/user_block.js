// Добавляем обработчик события на кнопку с id="blockButton"
document.getElementById('blockButton').addEventListener('click', function () {

    // Запрашиваем подтверждение действия у пользователя
    if (!confirm('Вы уверены, что хотите сменить статус пользователя?')) {
        // Если пользователь отменил действие, прерываем выполнение функции
        return;
    }

    // Получаем CSRF-токен из input с именем "csrf_token"
    const csrfToken = document.querySelector('input[name="csrf_token"]')?.value;
    // Получаем ID текущего пользователя (администратора) из input с именем "admin_userid"
    const adminUserId = document.querySelector('input[name="admin_userid"]')?.value;

    // Получаем значение input с id="userID"
    const userIDInput = document.getElementById('userID');
    const userIDValue = userIDInput ? userIDInput.value : null;

    // Проверяем наличие всех необходимых параметров
    const missingParams = [];
    if (!csrfToken) missingParams.push('CSRF-токен');
    if (!adminUserId) missingParams.push('ID текущего пользователя');
    if (!userIDValue) missingParams.push('ID пользователя');

    // Если какие-то параметры отсутствуют, выводим сообщение об ошибке и прерываем выполнение
    if (missingParams.length > 0) {
        const errorMessage = 'Ошибка 0031: Отсутствуют обязательные параметры: ' + missingParams.join(', ');
        showErrorMessage('error', 'Ошибка', errorMessage, 5000); // Новый формат сообщения
        return;
    }

    // Преобразуем значение userIDValue в массив (на случай, если нужно будет блокировать несколько пользователей)
    const selectedUsers = [userIDValue];

    // Отправляем POST-запрос на сервер для блокировки пользователя
    fetch('back/all_account/blockuser.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json' // Указываем, что отправляем данные в формате JSON
        },
        body: JSON.stringify({
            user_ids: selectedUsers, // ID пользователей для блокировки
            csrf_token: csrfToken,  // CSRF-токен для защиты от атак
            userid: adminUserId,    // ID текущего пользователя (администратора)
        })
    })
    .then(response => {
        // Преобразуем ответ сервера в JSON
        return response.json();
    })
    .then(data => {
        // Обрабатываем ответ сервера
        if (data.success) {
            // Если операция успешна, выводим сообщение и перезагружаем страницу
            showErrorMessage('success', 'Успех', 'Статус пользователя успешно изменен.', 3000); // Новый формат сообщения
            location.reload();
        } else {
            // Если произошла ошибка, выводим сообщение об ошибке
            showErrorMessage('error', 'Ошибка', data.message || 'Ошибка 0032: Произошла неизвестная ошибка.', 5000); // Новый формат сообщения
        }
    })
    .catch(error => {
        // Если произошла ошибка при выполнении запроса, выводим сообщение об ошибке
        showErrorMessage('error', 'Ошибка', 'Ошибка 0033: Произошла неизвестная ошибка.', 5000); // Новый формат сообщения
    });
});