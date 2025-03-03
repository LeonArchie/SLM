document.addEventListener("DOMContentLoaded", function () {
    // Получаем все кнопки
    const editButton = document.getElementById('editButton');
    const blockButton = document.getElementById('blockButton');
    const deleteButton = document.getElementById('deleteButton');

    // Начальное состояние кнопок
    editButton.disabled = true;
    blockButton.disabled = true;
    deleteButton.disabled = true;

    // Обработчик изменения состояния чекбоксов
    function updateButtonStates() {
        const selectedCheckboxes = Array.from(document.querySelectorAll('.userCheckbox:checked'));

        if (selectedCheckboxes.length === 1) {
            // Если выбран один пользователь, активируем все кнопки
            editButton.disabled = false;
            blockButton.disabled = false;
            deleteButton.disabled = false;
        } else if (selectedCheckboxes.length > 1) {
            // Если выбрано несколько пользователей, активируем только "Блокировать" и "Удалить"
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

    // Добавляем обработчик события для всех чекбоксов пользователей
    document.querySelectorAll('.userCheckbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateButtonStates);
    });

    // Обработчик для кнопки "Выбрать все"
    document.getElementById('selectAll').addEventListener('change', function () {
        const checkboxes = document.querySelectorAll('.userCheckbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateButtonStates(); // Обновляем состояние кнопок после выбора/снятия всех чекбоксов
    });

    // Обработчик для строк таблицы
    document.querySelectorAll('tbody tr').forEach(row => {
        row.addEventListener('click', function (event) {
            // Игнорируем клики по чекбоксам, чтобы не было конфликтов
            if (event.target.type === 'checkbox') return;

            const checkbox = row.querySelector('.userCheckbox');
            checkbox.checked = !checkbox.checked; // Переключаем состояние чекбокса
            row.classList.toggle('selected', checkbox.checked); // Добавляем/убираем класс для подсветки
            updateButtonStates(); // Обновляем состояние кнопок
        });
    });
});