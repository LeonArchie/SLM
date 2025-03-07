document.addEventListener("DOMContentLoaded", function () {
    // Получаем все кнопки
    const assignPrivilegesButton = document.getElementById('AssignPrivileges');
    const viewPrivilegesButton = document.getElementById('VievPrivileges');
    const OffPrivilegesButton = document.getElementById('OffPrivileges');
    const createPrivilegesButton = document.getElementById('CreatePrivileges');
    const DeletePrivilegesButton = document.getElementById('DeletePrivileges');

    // Начальное состояние кнопок
    assignPrivilegesButton.disabled = true;
    viewPrivilegesButton.disabled = true;
    OffPrivilegesButton.disabled = true;
    createPrivilegesButton.disabled = false;
    DeletePrivilegesButton.disabled = false;

    // Обработчик изменения состояния чекбоксов
    function updateButtonStates() {
        const selectedCheckboxes = Array.from(document.querySelectorAll('.userCheckbox:checked'));

        if (selectedCheckboxes.length === 1) {
            // Если выбран один пользователь, активируем определенные кнопки
            assignPrivilegesButton.disabled = false;
            viewPrivilegesButton.disabled = false;
            OffPrivilegesButton.disabled = false;
            createPrivilegesButton.disabled = true;
            DeletePrivilegesButton.disabled = true;
        } else if (selectedCheckboxes.length > 1) {
            // Если выбрано несколько пользователей, активируем только "Назначить полномочия" и "Снять полномочия"
            assignPrivilegesButton.disabled = false;
            viewPrivilegesButton.disabled = true;
            OffPrivilegesButton.disabled = false;
            createPrivilegesButton.disabled = true;
            DeletePrivilegesButton.disabled = true;
        } else {
            // Если ни один пользователь не выбран, отключаем все кнопки
            assignPrivilegesButton.disabled = true;
            viewPrivilegesButton.disabled = true;
            OffPrivilegesButton.disabled = true;
            createPrivilegesButton.disabled = false;
            DeletePrivilegesButton.disabled = false;
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