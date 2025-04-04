document.addEventListener('DOMContentLoaded', function() {
    // Элементы DOM
    const selectAllCheckbox = document.getElementById('selectAll');
    const serverCheckboxes = document.querySelectorAll('.serverCheckbox');
    const viewCardButton = document.getElementById('VievCardServer');
    
    // Обработчик для кнопки "Просмотреть карточку оборудования"
    viewCardButton.addEventListener('click', function() {
        // Находим выбранный чекбокс
        const checkedCheckbox = document.querySelector('.serverCheckbox:checked');
        
        // Если чекбокс выбран (должен быть только один, так как кнопка disabled при multiple выборе)
        if (checkedCheckbox) {
            const serverId = checkedCheckbox.dataset.serverid;
            // Выполняем редирект с передачей servId
            window.location.href = `/modules/AccInf/ServerCard.php?servId=${encodeURIComponent(serverId)}`;
        }
    });

    // Обновляем состояние кнопки при изменении выбора чекбоксов
    function updateViewCardButton() {
        const checkedCount = document.querySelectorAll('.serverCheckbox:checked').length;
        viewCardButton.disabled = checkedCount !== 1;
    }

    // Вешаем обработчики на все чекбоксы для обновления состояния кнопки
    serverCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateViewCardButton);
    });

    // Инициализация состояния кнопки при загрузке
    updateViewCardButton();
});