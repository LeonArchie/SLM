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
    
    // Обработка кликов по карточкам пользователей
    document.addEventListener('click', function(e) {
        const card = e.target.closest('.contact-card');
        if (card) {
            e.preventDefault();
            const userId = card.getAttribute('data-user-id');
            if (userId) {
                showUserProfile(userId);
            }
        }
    });
    
    // Функция поиска
    function performSearch(query) {
        const cards = document.querySelectorAll('.contact-card');
        let visibleCount = 0;
        
        cards.forEach(card => {
            const name = card.querySelector('h3').textContent.toLowerCase();
            const position = card.querySelector('.position').textContent.toLowerCase();
            const department = card.querySelector('.field-value:last-child').textContent.toLowerCase();
            const email = card.querySelectorAll('.field-value')[1].textContent.toLowerCase();
            
            const matches = query === '' || 
                name.includes(query) || 
                position.includes(query) || 
                department.includes(query) || 
                email.includes(query);
            
            card.style.display = matches ? '' : 'none';
            if (matches) visibleCount++;
        });
        
        // Показываем сообщение, если ничего не найдено
        const emptyState = document.querySelector('.empty-state');
        if (visibleCount === 0 && cards.length > 0) {
            if (!emptyState) {
                const grid = document.querySelector('#contactsGrid');
                if (grid) {
                    const noResults = document.createElement('div');
                    noResults.className = 'empty-state address-book-empty';
                    noResults.innerHTML = `
                        <span class="empty-icon"></span>
                        <p>Ничего не найдено</p>
                    `;
                    grid.parentNode.insertBefore(noResults, grid.nextSibling);
                }
            }
        } else if (emptyState && emptyState.textContent.includes('Ничего не найдено')) {
            emptyState.remove();
        }
    }
    
    // Функция для отображения профиля пользователя
    function showUserProfile(userId) {
        console.log('Открываем профиль пользователя:', userId);
        // Здесь можно реализовать открытие модального окна с подробной информацией
    }
    
    // Инициализация при загрузке
    initAddressBook();
    
    function initAddressBook() {
        // Можно добавить дополнительную инициализацию при необходимости
    }
});