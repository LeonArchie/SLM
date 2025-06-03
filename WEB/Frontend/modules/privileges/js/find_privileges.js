document.addEventListener('DOMContentLoaded', function() {
    // Поиск пользователей
    const searchInput = document.getElementById('userSearch');
    const usersTable = document.querySelector('.table-container table');
    
    if (searchInput && usersTable) {
        function searchUsers() {
            const searchTerm = searchInput.value.trim().toLowerCase();
            const rows = usersTable.querySelectorAll('tbody tr:not(.no-data-row)');
            let visibleCount = 0;
            
            rows.forEach(row => {
                let rowText = '';
                const cells = row.querySelectorAll('td');
                
                cells.forEach(cell => {
                    // Пропускаем ячейки с чекбоксами
                    if (!cell.querySelector('.userCheckbox') && !cell.querySelector('.status-indicator')) {
                        rowText += ' ' + cell.textContent.toLowerCase();
                    }
                    
                    // Добавляем состояние активности
                    const activeCheckbox = cell.querySelector('.status-indicator');
                    if (activeCheckbox && activeCheckbox.checked) {
                        rowText += ' активен';
                    } else if (activeCheckbox) {
                        rowText += ' неактивен';
                    }
                });
                
                if (searchTerm === '' || rowText.includes(searchTerm)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Показываем сообщение, если ничего не найдено
            const noDataRow = usersTable.querySelector('.no-data-row');
            if (visibleCount === 0 && searchTerm !== '') {
                if (!noDataRow) {
                    const row = document.createElement('tr');
                    row.className = 'no-data-row';
                    row.innerHTML = '<td colspan="5" class="no-data-message">Пользователи не найдены</td>';
                    usersTable.querySelector('tbody').appendChild(row);
                }
            } else if (noDataRow) {
                noDataRow.remove();
            }
        }
        
        searchInput.addEventListener('input', searchUsers);
        
        // Инициализация поиска при загрузке
        searchUsers();
    }

    // Обработка чекбоксов
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.userCheckbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            updateButtonStates();
        });
    }

    // Обновление состояния кнопок при выборе пользователей
    function updateButtonStates() {
        const selectedCount = document.querySelectorAll('.userCheckbox:checked').length;
        document.getElementById('VievPrivileges').disabled = selectedCount !== 1;
        document.getElementById('AssignPrivileges').disabled = selectedCount === 0;
        document.getElementById('OffPrivileges').disabled = selectedCount === 0;
    }

    // Навешиваем обработчики на чекбоксы пользователей
    document.querySelectorAll('.userCheckbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateButtonStates);
    });

    // Инициализация состояния кнопок
    updateButtonStates();
});