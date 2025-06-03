document.addEventListener('DOMContentLoaded', function() {
    // Элементы DOM
    const searchInput = document.getElementById('serverSearch');
    const scriptCards = document.querySelectorAll('.script-card');
    const emptyMessage = document.createElement('div');
    emptyMessage.className = 'empty-message';
    emptyMessage.textContent = 'Сценарии не найдены';
    const scriptsGrid = document.querySelector('.scripts-grid');

    // Обработчик поиска
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.trim().toLowerCase();
        let hasMatches = false;

        // Проверяем каждую карточку на соответствие поисковому запросу
        scriptCards.forEach(card => {
            const id = card.querySelector('.script-id').textContent.toLowerCase();
            const title = card.querySelector('.script-title').textContent.toLowerCase();
            const description = card.querySelector('.script-description').textContent.toLowerCase();
            const tags = Array.from(card.querySelectorAll('.tag')).map(tag => tag.textContent.toLowerCase());

            // Проверяем совпадение в любом из полей
            const matches = id.includes(searchTerm) || 
                          title.includes(searchTerm) || 
                          description.includes(searchTerm) || 
                          tags.some(tag => tag.includes(searchTerm));

            if (matches) {
                card.style.display = 'block';
                hasMatches = true;
            } else {
                card.style.display = 'none';
            }
        });

        // Показываем сообщение, если ничего не найдено
        const existingEmptyMessage = scriptsGrid.querySelector('.empty-message');
        if (!hasMatches) {
            if (!existingEmptyMessage) {
                scriptsGrid.appendChild(emptyMessage);
            }
        } else {
            if (existingEmptyMessage) {
                scriptsGrid.removeChild(existingEmptyMessage);
            }
        }
    });

    // Анимация при изменении результатов поиска
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                const card = mutation.target;
                if (card.style.display === 'block') {
                    card.style.animation = 'fadeIn 0.3s ease forwards';
                }
            }
        });
    });

    scriptCards.forEach(card => {
        observer.observe(card, { attributes: true });
    });
});

// Добавляем стили для анимации прямо в JS
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .script-card {
        animation: fadeIn 0.3s ease forwards;
    }
    
    .empty-message {
        grid-column: 1 / -1;
        text-align: center;
        padding: 40px;
        color: #7f8c8d;
        font-size: 1.1rem;
    }
`;
document.head.appendChild(style);