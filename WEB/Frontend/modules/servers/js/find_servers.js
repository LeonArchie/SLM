document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('serverSearch');
    const serversTable = document.getElementById('serversTable');
    const serverRows = serversTable.querySelectorAll('tbody tr');
    
    // Функция для поиска серверов
    function searchServers() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        
        serverRows.forEach(row => {
            // Получаем все текстовые данные из строки таблицы
            const rowData = Array.from(row.cells).map(cell => {
                // Для чекбоксов получаем значение из data-атрибута
                if (cell.querySelector('input[type="checkbox"]')) {
                    return cell.querySelector('input[type="checkbox"]').dataset.serverid || '';
                }
                // Для ячеек с чекбоксами статусов получаем состояние
                if (cell.querySelector('.demon-indicator') || cell.querySelector('.validate-indicator')) {
                    return cell.querySelector('input').checked ? 'да' : 'нет';
                }
                // Для обычных ячеек получаем текст
                return cell.textContent.toLowerCase().trim();
            }).join(' ');
            
            // Показываем/скрываем строку в зависимости от совпадения
            row.style.display = rowData.includes(searchTerm) ? '' : 'none';
        });
    }
    
    // Обработчик события ввода в поле поиска
    searchInput.addEventListener('input', searchServers);
    
    // Обработчик события нажатия клавиш (для быстрого сброса поиска)
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            searchInput.value = '';
            searchServers();
        }
    });
});