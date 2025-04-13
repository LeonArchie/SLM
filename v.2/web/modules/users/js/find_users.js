document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('userSearch');
    const searchButton = document.getElementById('searchButton');
    const usersTable = document.getElementById('usersTable');
    
    // Функция для поиска пользователей
    function searchUsers() {
        const searchTerm = searchInput.value.trim().toLowerCase();
        const rows = usersTable.querySelectorAll('.user-row');
        
        if (searchTerm === '') {
            // Если строка поиска пуста, показать всех пользователей
            rows.forEach(row => {
                row.style.display = '';
            });
            return;
        }
        
        let found = false;
        
        rows.forEach(row => {
            const fullName = row.getAttribute('data-fullname');
            const login = row.getAttribute('data-login');
            
            if (fullName.includes(searchTerm) || login.includes(searchTerm)) {
                row.style.display = '';
                found = true;
            } else {
                row.style.display = 'none';
            }
        });
        
        if (!found) {
            showErrorMessage('warning', 'Внимание', 'Пользователи не найдены.', 3000);
        }
    }
    
    // Обработчики событий
    searchButton.addEventListener('click', searchUsers);
    
    searchInput.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            searchUsers();
        }
    });
    
    // Функция для сброса поиска
    window.resetSearch = function() {
        searchInput.value = '';
        searchUsers();
    };
});