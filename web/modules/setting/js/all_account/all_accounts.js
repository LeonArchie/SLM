document.addEventListener("DOMContentLoaded", function () {
    const editButton = document.getElementById('editButton');
    const blockButton = document.getElementById('blockButton');
    const deleteButton = document.getElementById('deleteButton');

    editButton.disabled = true;
    blockButton.disabled = true;
    deleteButton.disabled = true;

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

    document.querySelectorAll('.userCheckbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateButtonStates);
    });

    document.getElementById('selectAll').addEventListener('change', function () {
        const checkboxes = document.querySelectorAll('.userCheckbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateButtonStates();
    });

    document.querySelectorAll('tbody tr').forEach(row => {
        row.addEventListener('click', function (event) {
            if (event.target.type === 'checkbox') return;

            const checkbox = row.querySelector('.userCheckbox');
            checkbox.checked = !checkbox.checked;
            row.classList.toggle('selected', checkbox.checked);
            updateButtonStates();
        });
    });
});