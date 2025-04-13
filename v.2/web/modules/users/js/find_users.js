document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('userSearch');
    const usersTable = document.getElementById('usersTable');
    
    // Функция для поиска пользователей по всем полям
    function searchUsers() {
        const searchTerm = searchInput.value.trim().toLowerCase();
        const rows = usersTable.querySelectorAll('tbody tr:not(.no-data-row)');
        let visibleCount = 0;
        
        rows.forEach(row => {
            // Собираем текст из всех ячеек строки
            let rowText = '';
            const cells = row.querySelectorAll('td');
            
            cells.forEach(cell => {
                // Пропускаем ячейки с чекбоксами (они не содержат текста для поиска)
                if (!cell.querySelector('.userCheckbox') && 
                    !cell.querySelector('.status-indicator') && 
                    !cell.querySelector('.ldap-indicator')) {
                    rowText += ' ' + cell.textContent.toLowerCase();
                }
                
                // Добавляем состояние чекбоксов
                const activeCheckbox = cell.querySelector('.status-indicator');
                const ldapCheckbox = cell.querySelector('.ldap-indicator');
                
                if (activeCheckbox && activeCheckbox.checked) {
                    rowText += ' активен';
                } else if (activeCheckbox) {
                    rowText += ' неактивен';
                }
                
                if (ldapCheckbox && ldapCheckbox.checked) {
                    rowText += ' ldap';
                }
            });
            
            // Ищем совпадения
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
    
    // Обработчик события ввода - поиск при каждом изменении
    searchInput.addEventListener('input', searchUsers);
    
    // Обработчик события загрузки страницы - инициализация
    searchUsers();
    
    // Функция для сброса поиска
    window.resetSearch = function() {
        searchInput.value = '';
        searchUsers();
    };
});