// Обработка клика по строке таблицы
document.addEventListener('click', function(e) {
    const row = e.target.closest('tr.user-row');
    if (!row) return;

    // Если клик был по аватарке - сохраняем текущее поведение (перенаправление)
    if (e.target.closest('.user-avatar')) {
        const userId = row.querySelector('.userCheckbox').dataset.userid;
        redirectToEditUser(userId);
        return;
    }

    // Для кликов по другим элементам строки - переключаем чекбокс
    const checkbox = row.querySelector('.userCheckbox');
    if (checkbox && !e.target.closest('a')) { // Исключаем клики по ссылкам
        checkbox.checked = !checkbox.checked;
        updateButtonStates();
    }
});

// Обработка клика по заголовку "Выбрать все"
document.getElementById('selectAll')?.addEventListener('click', function(e) {
    const checkboxes = document.querySelectorAll('.userCheckbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = e.target.checked;
    });
    updateButtonStates();
});

// Обновление состояния кнопок в зависимости от выбранных чекбоксов
function updateButtonStates() {
    const anyChecked = document.querySelectorAll('.userCheckbox:checked').length > 0;
    document.getElementById('editButton').disabled = !anyChecked;
    document.getElementById('blockButton').disabled = !anyChecked;
    document.getElementById('syncLdapButton').disabled = !anyChecked;
}

// Обработка изменений чекбоксов для обновления состояния кнопок
document.querySelectorAll('.userCheckbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateButtonStates);
});

function redirectToEditUser(userId) {
    // код для перехода к редактированию пользователя
    console.log('Redirect to edit user:', userId);
    // window.location.href = `/edit-user?id=${userId}`;
}

// Инициализация состояния кнопок при загрузке
document.addEventListener('DOMContentLoaded', updateButtonStates);