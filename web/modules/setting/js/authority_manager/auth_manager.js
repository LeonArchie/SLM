document.addEventListener("DOMContentLoaded", function () {
    const assignPrivilegesButton = document.getElementById('AssignPrivileges');
    const viewPrivilegesButton = document.getElementById('VievPrivileges');
    const OffPrivilegesButton = document.getElementById('OffPrivileges');
    const createPrivilegesButton = document.getElementById('CreatePrivileges');
    const DeletePrivilegesButton = document.getElementById('DeletePrivileges');

    assignPrivilegesButton.disabled = true;
    viewPrivilegesButton.disabled = true;
    OffPrivilegesButton.disabled = true;
    createPrivilegesButton.disabled = false;
    DeletePrivilegesButton.disabled = false;

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