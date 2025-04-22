document.addEventListener('DOMContentLoaded', function() {
    // Обработка клика по строке таблицы
    const userRows = document.querySelectorAll('#usersTable tbody tr.user-row');
    
    userRows.forEach(row => {
        // Исключаем клики по чекбоксам и ссылкам
        row.addEventListener('click', function(e) {
            // Если клик был по чекбоксу или ссылке, не обрабатываем
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'A') {
                return;
            }
            
            // Находим чекбокс в текущей строке
            const checkbox = this.querySelector('.userCheckbox');
            if (checkbox) {
                // Инвертируем состояние чекбокса
                checkbox.checked = !checkbox.checked;
                
                // Обновляем состояние кнопок
                updateButtonStates();
                
                // Обновляем состояние "Выбрать все"
                updateSelectAllCheckbox();
            }
        });
    });
    
    // Функция для обновления состояния кнопок
    function updateButtonStates() {
        const selectedCount = document.querySelectorAll('.userCheckbox:checked').length;
        document.getElementById('VievPrivileges').disabled = selectedCount !== 1;
        document.getElementById('AssignPrivileges').disabled = selectedCount === 0;
        document.getElementById('OffPrivileges').disabled = selectedCount === 0;
    }
    
    // Функция для обновления состояния чекбокса "Выбрать все"
    function updateSelectAllCheckbox() {
        const checkboxes = document.querySelectorAll('.userCheckbox');
        const selectAll = document.getElementById('selectAll');
        
        if (checkboxes.length === 0) return;
        
        const allChecked = Array.from(checkboxes).every(checkbox => checkbox.checked);
        const someChecked = Array.from(checkboxes).some(checkbox => checkbox.checked);
        
        selectAll.checked = allChecked;
        selectAll.indeterminate = someChecked && !allChecked;
    }
});