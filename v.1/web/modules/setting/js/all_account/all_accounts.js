// Ожидаем, пока весь HTML-документ будет загружен и готов к взаимодействию
document.addEventListener("DOMContentLoaded", function () {

    // Получаем ссылки на кнопки "Редактировать", "Блокировать" и "Удалить" по их ID
    const editButton = document.getElementById('editButton');
    const blockButton = document.getElementById('blockButton');
    const deleteButton = document.getElementById('deleteButton');

    // Изначально отключаем все кнопки, так как ни один пользователь не выбран
    editButton.disabled = true;
    blockButton.disabled = true;
    deleteButton.disabled = true;

    // Функция для обновления состояния кнопок в зависимости от выбранных пользователей
    function updateButtonStates() {
        // Получаем все выбранные чекбоксы пользователей
        const selectedCheckboxes = Array.from(document.querySelectorAll('.userCheckbox:checked'));

        if (selectedCheckboxes.length === 1) {
            // Если выбран только один пользователь, активируем все кнопки
            editButton.disabled = false;
            blockButton.disabled = false;
            deleteButton.disabled = false;
        } else if (selectedCheckboxes.length > 1) {
            // Если выбрано несколько пользователей, активируем только кнопки "Блокировать" и "Удалить"
            editButton.disabled = true;
            blockButton.disabled = false;
            deleteButton.disabled = false;
        } else {
            // Если ни один пользователь не выбран, отключаем все кнопки
            editButton.disabled = true;
            blockButton.disabled = true;
            deleteButton.disabled = true;
        }
    }

    // Добавляем обработчик события "change" для каждого чекбокса пользователя
    document.querySelectorAll('.userCheckbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateButtonStates);
    });

    // Добавляем обработчик события "change" для чекбокса "Выбрать все"
    document.getElementById('selectAll').addEventListener('change', function () {
        // Получаем все чекбоксы пользователей
        const checkboxes = document.querySelectorAll('.userCheckbox');
        // Устанавливаем состояние всех чекбоксов в соответствии с состоянием "Выбрать все"
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        // Обновляем состояние кнопок
        updateButtonStates();
    });

    // Добавляем обработчик события "click" для каждой строки таблицы
    document.querySelectorAll('tbody tr').forEach(row => {
        row.addEventListener('click', function (event) {
            // Игнорируем клики по самому чекбоксу, чтобы избежать двойного переключения
            if (event.target.type === 'checkbox') return;

            // Находим чекбокс в текущей строке и переключаем его состояние
            const checkbox = row.querySelector('.userCheckbox');
            checkbox.checked = !checkbox.checked;
            // Добавляем или удаляем класс "selected" в зависимости от состояния чекбокса
            row.classList.toggle('selected', checkbox.checked);
            // Обновляем состояние кнопок
            updateButtonStates();
        });
    });
});