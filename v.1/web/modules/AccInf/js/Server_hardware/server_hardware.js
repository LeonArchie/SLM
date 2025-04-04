document.addEventListener('DOMContentLoaded', function() {
    // Элементы DOM
    const selectAllCheckbox = document.getElementById('selectAll');
    const serverCheckboxes = document.querySelectorAll('.serverCheckbox');
    const viewCardButton = document.getElementById('VievCardServer');
    const addServerButton = document.getElementById('AddServers');
    
    // Обработчик для чекбокса "Выбрать все"
    selectAllCheckbox.addEventListener('change', function() {
        const isChecked = this.checked;
        serverCheckboxes.forEach(checkbox => {
            checkbox.checked = isChecked;
        });
        updateViewCardButtonState();
    });
    
    // Обработчики для чекбоксов серверов
    serverCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // Если сняли выбор с одного из чекбоксов, снимаем "Выбрать все"
            if (!this.checked && selectAllCheckbox.checked) {
                selectAllCheckbox.checked = false;
            }
            updateViewCardButtonState();
        });
    });

    // Обработчики клика по строкам таблицы
    document.querySelectorAll('tbody tr').forEach(row => {
        row.addEventListener('click', function(e) {
            // Игнорируем клики по ссылкам и чекбоксам
            if (e.target.tagName === 'A' || e.target.tagName === 'INPUT') {
                return;
            }
            
            const checkbox = this.querySelector('.serverCheckbox');
            if (checkbox) {
                checkbox.checked = !checkbox.checked;
                // Создаем и запускаем событие change
                const event = new Event('change');
                checkbox.dispatchEvent(event);
            }
        });
    });
    
    // Функция для обновления состояния кнопки "Просмотреть карточку"
    function updateViewCardButtonState() {
        const checkedCount = document.querySelectorAll('.serverCheckbox:checked').length;
        viewCardButton.disabled = checkedCount !== 1;
    }
    
    // Обработчик для кнопки "Просмотреть карточку оборудования"
    viewCardButton.addEventListener('click', function() {
        if (this.disabled) return;
        
        const checkedCheckbox = document.querySelector('.serverCheckbox:checked');
        if (checkedCheckbox) {
            const serverId = checkedCheckbox.dataset.serverid;
            redirectToServerCard(serverId);
        }
    });
   
    // Инициализация состояния кнопки при загрузке
    updateViewCardButtonState();
});