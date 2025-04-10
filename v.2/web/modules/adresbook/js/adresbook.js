document.addEventListener('DOMContentLoaded', function() {
    // Инициализация поиска с debounce
    const searchInput = document.getElementById('searchInput');
    let searchTimeout;
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch(this.value.trim().toLowerCase());
            }, 300);
        });
    }
    
    // Обработка кликов по пользователям
    document.addEventListener('click', function(e) {
        if (e.target.closest('.name-cell a')) {
            e.preventDefault();
            const userId = e.target.closest('a').getAttribute('data-user-id');
            if (userId) {
                showUserProfile(userId);
            }
        }
    });
    
    // Функция поиска
    function performSearch(query) {
        const rows = document.querySelectorAll('#contactsTable tbody tr');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const name = row.cells[0].textContent.toLowerCase();
            const position = row.cells[1]?.textContent.toLowerCase() || '';
            const department = row.cells[2]?.textContent.toLowerCase() || '';
            const email = row.cells[3]?.textContent.toLowerCase() || '';
            
            const matches = query === '' || 
                name.includes(query) || 
                position.includes(query) || 
                department.includes(query) || 
                email.includes(query);
            
            row.style.display = matches ? '' : 'none';
            if (matches) visibleCount++;
        });
        
        // Показываем сообщение, если ничего не найдено
        const emptyState = document.querySelector('.empty-state');
        if (visibleCount === 0 && rows.length > 0) {
            if (!emptyState) {
                const table = document.querySelector('#contactsTable');
                if (table) {
                    const noResults = document.createElement('div');
                    noResults.className = 'empty-state';
                    noResults.innerHTML = `
                        <i class="fas fa-search"></i>
                        <p>Ничего не найдено</p>
                    `;
                    table.parentNode.insertBefore(noResults, table.nextSibling);
                }
            }
        } else if (emptyState && emptyState.textContent.includes('Ничего не найдено')) {
            emptyState.remove();
        }
    }
    
    // Функция для отображения профиля пользователя
    function showUserProfile(userId) {
        // Здесь можно реализовать модальное окно
        console.log('Открываем профиль пользователя:', userId);
        
        // 
       // fetch(`/api/user/${userId}`)
        //    .then(response => {
        //        if (!response.ok) {
        //            throw new Error('Ошибка сервера');
       //         }
 //               return response.json();
   //         })
     //       .then(data => {
       //         // openModal(data);
         //       showErrorMessage('success', 'Успех', 'Данные пользователя загружены', 3000);
 //           })
   //         .catch(error => {
     //           console.error('Ошибка:', error);
       //         showErrorMessage('error', 'Ошибка', 'Не удалось загрузить профиль', 5000);
         //   });
    }
    
    // Инициализация при загрузке
    initAddressBook();
    
    function initAddressBook() {
        // Можно добавить проверку загрузки данных
        if (document.querySelector('#contactsTable tbody tr')) {
            showErrorMessage('success', 'Готово', 'Адресная книга загружена', 2000);
        }
    }
});