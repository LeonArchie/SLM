document.addEventListener('DOMContentLoaded', function() {
    // Получаем все элементы формы, которые нужно отслеживать
    const formInputs = document.querySelectorAll('.scrollable-form input:not([readonly]):not([type="hidden"]), .scrollable-form select, .scrollable-form textarea');
    const checkboxes = document.querySelectorAll('.scrollable-form input[type="checkbox"]');
    const saveButton = document.getElementById('saveButton');
    
    // Исходные значения полей
    let initialValues = {};
    
    // Сохраняем исходные значения всех полей
    function storeInitialValues() {
        formInputs.forEach(input => {
            if (input.type === 'checkbox') {
                initialValues[input.name] = input.checked;
            } else {
                initialValues[input.name] = input.value;
            }
        });
        
        // Изначально кнопка "Сохранить" неактивна
        saveButton.disabled = true;
    }
    
    // Проверяем, были ли изменения
    function checkForChanges() {
        let hasChanges = false;
        
        formInputs.forEach(input => {
            if (input.type === 'checkbox') {
                if (initialValues[input.name] !== input.checked) {
                    hasChanges = true;
                }
            } else {
                if (initialValues[input.name] !== input.value) {
                    hasChanges = true;
                }
            }
        });
        
        saveButton.disabled = !hasChanges;
    }
    
    // Инициализация
    storeInitialValues();
    
    // Добавляем обработчики событий для всех полей ввода
    formInputs.forEach(input => {
        if (input.type === 'checkbox') {
            input.addEventListener('change', checkForChanges);
        } else {
            input.addEventListener('input', checkForChanges);
        }
    });
    
    // Обработчик для кнопки "Сохранить"
    saveButton.addEventListener('click', function() {
        // После сохранения обновляем исходные значения
        storeInitialValues();
    });
    
    // Обработчик для кнопки "Обновить" (если она есть на странице)
    const updateButton = document.getElementById('updateButton');
    if (updateButton) {
        updateButton.addEventListener('click', function() {
            // При обновлении страницы значения сбросятся автоматически
            saveButton.disabled = true;
        });
    }
});