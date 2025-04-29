document.addEventListener('DOMContentLoaded', function() {
    // Обработчик клика на кнопку "Редактировать"
    document.getElementById('editButton')?.addEventListener('click', handleEditUser);
    
    // Обновляем состояние кнопки при изменении выбора
    document.querySelectorAll('.userCheckbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateEditButtonState);
    });
    
    // Инициализируем состояние кнопки
    updateEditButtonState();
});

// Обработчик редактирования пользователя
function handleEditUser() {
    const selectedUsers = getSelectedUsers();
    
    // Проверяем, что выбран ровно один пользователь
    if (selectedUsers.length !== 1) {
        showErrorMessage('warning', 'Внимание', 'Пожалуйста, выберите одного пользователя для редактирования.', 3000);
        return;
    }
    
    const userId = selectedUsers[0];
    window.location.href = `/modules/edit_users/edit_users.php?userid=${encodeURIComponent(userId)}`;
}

// Функция для получения выбранных пользователей
function getSelectedUsers() {
    return Array.from(document.querySelectorAll('.userCheckbox:checked'))
        .map(checkbox => checkbox.dataset.userid);
}

// Обновление состояния кнопки "Редактировать"
function updateEditButtonState() {
    const editButton = document.getElementById('editButton');
    if (!editButton) return;
    
    const selectedCount = document.querySelectorAll('.userCheckbox:checked').length;
    editButton.disabled = selectedCount !== 1;
}
