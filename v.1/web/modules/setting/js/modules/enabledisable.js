// Объявляем переменную для хранения выбранного GUID модуля
let selectedModuleGuid = null;

// Обработчик клика по строкам таблицы для выбора модуля
document.querySelectorAll('#modulesTable tbody tr').forEach(row => {
    row.addEventListener('click', function() {
        // Снимаем выделение со всех строк
        document.querySelectorAll('#modulesTable tbody tr').forEach(r => {
            r.classList.remove('selected');
        });
        
        // Выделяем текущую строку
        this.classList.add('selected');
        selectedModuleGuid = this.dataset.guid;
        document.getElementById('EnablaDisableButton').disabled = false;
    });
});

// Обработчик кнопки "Включить/Отключить модуль"
document.getElementById('EnablaDisableButton').addEventListener('click', function() {
    if (!selectedModuleGuid) {
        showErrorMessage('error', 'Ошибка', 'Ошибка 0060: Не выбран ни один модуль', 5000);
        return;
    }

    const checkbox = document.querySelector(`tr[data-guid="${selectedModuleGuid}"] .active-checkbox`);
    const newState = !checkbox.checked;
    checkbox.checked = newState;

    fetch('back/modules/toggle_module.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            guid: selectedModuleGuid,
            active: newState,
            source: 'toggle_button'
        })
    })
    .then(response => {
        if (!response.ok) throw new Error('Ошибка сети');
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showErrorMessage('success', 'Успех', 'Изменения успешно сохранены', 5000);
        }
        if (!data.success) {
            checkbox.checked = !newState;
            showErrorMessage('error', 'Ошибка', `Ошибка 0061: ${data.message || 'Не удалось изменить состояние модуля'}`, 5000);
        }
    })
    .catch(error => {
        checkbox.checked = !newState;
        showErrorMessage('error', 'Ошибка', `Ошибка 0062: ${error.message}`, 5000);
    });
});