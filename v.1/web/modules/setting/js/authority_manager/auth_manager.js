// Ожидаем, пока весь HTML-документ будет загружен и готов к взаимодействию
document.addEventListener("DOMContentLoaded", function () {

    // Получаем ссылки на кнопки управления привилегиями по их ID
    const assignPrivilegesButton = document.getElementById('AssignPrivileges');
    const viewPrivilegesButton = document.getElementById('VievPrivileges');
    const OffPrivilegesButton = document.getElementById('OffPrivileges');
    const createPrivilegesButton = document.getElementById('CreatePrivileges');
    const DeletePrivilegesButton = document.getElementById('DeletePrivileges');

    // Изначально отключаем кнопки, связанные с управлением привилегиями
    assignPrivilegesButton.disabled = true;
    viewPrivilegesButton.disabled = true;
    OffPrivilegesButton.disabled = true;
    // Кнопки создания и удаления привилегий оставляем активными
    createPrivilegesButton.disabled = false;
    DeletePrivilegesButton.disabled = false;

    // Функция для обновления состояния кнопок в зависимости от выбранных пользователей
    function updateButtonStates() {
        // Получаем все отмеченные чекбоксы с классом 'userCheckbox'
        const selectedCheckboxes = Array.from(document.querySelectorAll('.userCheckbox:checked'));

        if (selectedCheckboxes.length === 1) {
            // Если выбран один пользователь, активируем кнопки для работы с одним пользователем
            assignPrivilegesButton.disabled = false;
            viewPrivilegesButton.disabled = false;
            OffPrivilegesButton.disabled = false;
            // Отключаем кнопки создания и удаления привилегий
            createPrivilegesButton.disabled = true;
            DeletePrivilegesButton.disabled = true;
        } else if (selectedCheckboxes.length > 1) {
            // Если выбрано несколько пользователей, активируем только кнопки для массового управления
            assignPrivilegesButton.disabled = false;
            viewPrivilegesButton.disabled = true; // Просмотр привилегий недоступен для нескольких пользователей
            OffPrivilegesButton.disabled = false;
            // Отключаем кнопки создания и удаления привилегий
            createPrivilegesButton.disabled = true;
            DeletePrivilegesButton.disabled = true;
        } else {
            // Если ни один пользователь не выбран, отключаем все кнопки, кроме создания и удаления
            assignPrivilegesButton.disabled = true;
            viewPrivilegesButton.disabled = true;
            OffPrivilegesButton.disabled = true;
            createPrivilegesButton.disabled = false;
            DeletePrivilegesButton.disabled = false;
        }
    }

    // Добавляем обработчик события 'change' для каждого чекбокса с классом 'userCheckbox'
    document.querySelectorAll('.userCheckbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateButtonStates);
    });

    // Добавляем обработчик события 'change' для чекбокса "Выбрать все"
    document.getElementById('selectAll').addEventListener('change', function () {
        // Получаем все чекбоксы с классом 'userCheckbox'
        const checkboxes = document.querySelectorAll('.userCheckbox');
        // Устанавливаем состояние всех чекбоксов в соответствии с состоянием "Выбрать все"
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        // Обновляем состояние кнопок
        updateButtonStates();
    });

    // Добавляем обработчик события 'click' для каждой строки таблицы
    document.querySelectorAll('tbody tr').forEach(row => {
        row.addEventListener('click', function (event) {
            // Игнорируем клики по самому чекбоксу, чтобы избежать двойного переключения
            if (event.target.type === 'checkbox') return;

            // Находим чекбокс в текущей строке и переключаем его состояние
            const checkbox = row.querySelector('.userCheckbox');
            checkbox.checked = !checkbox.checked;
            // Добавляем или удаляем класс 'selected' для визуального выделения строки
            row.classList.toggle('selected', checkbox.checked);
            // Обновляем состояние кнопок
            updateButtonStates();
        });
    });
});