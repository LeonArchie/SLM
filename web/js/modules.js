// Переключение режима редактирования
const editButton = document.getElementById('editButton');
const saveButton = document.getElementById('saveButton');
const addButton = document.getElementById('addButton');
const mainContainer = document.querySelector('main');

// Блокируем кнопки "Сохранить" и "Добавить модуль" при загрузке страницы
saveButton.disabled = true;
addButton.disabled = true;
saveButton.style.backgroundColor = '#ccc';
addButton.style.backgroundColor = '#ccc';

editButton.addEventListener('click', (e) => {
    e.preventDefault();

    // Включаем режим редактирования
    mainContainer.classList.add('edit-mode');

    // Делаем кнопку "Изменить" серой и неактивной
    editButton.disabled = true;
    editButton.style.backgroundColor = '#ccc';

    // Включаем кнопки "Сохранить" и "Добавить модуль"
    saveButton.disabled = false;
    addButton.disabled = false;

    // Убираем серый цвет с кнопок, если они активны
    saveButton.style.backgroundColor = '#28a745'; // Зелёный для "Сохранить"
    addButton.style.backgroundColor = '#ffc107'; // Жёлтый для "Добавить модуль"

    // Разрешаем редактирование всех полей и чекбоксов
    const inputs = mainContainer.querySelectorAll('input:not([type="checkbox"])');
    const checkboxes = mainContainer.querySelectorAll('input[type="checkbox"]');

    inputs.forEach(input => {
        input.readOnly = false; // Разрешаем редактирование текстовых полей
        input.style.backgroundColor = '#fff'; // Белый фон для редактируемых полей
        input.style.border = '1px solid #007BFF'; // Синяя рамка для активных полей
    });

    checkboxes.forEach(checkbox => {
        checkbox.disabled = false; // Разрешаем выбор чекбоксов
    });
});

// Обработка кнопки "Сохранить"
saveButton.addEventListener('click', (e) => {
    e.preventDefault();
    if (confirm('Вы уверены, что хотите сохранить изменения?')) {
        // Собираем данные из таблицы
        const tableData = [];
        const rows = mainContainer.querySelectorAll('table tbody tr');

        rows.forEach(row => {
            const inputs = row.querySelectorAll('input');
            const data = {
                active: inputs[0].checked,
                title: inputs[1].value,
                guid: inputs[2].value,
                parentTitle: inputs[3].value,
                url: inputs[4].value,
                icon: inputs[5].value,
                role: []
            };

            // Собираем роли
            if (inputs[6].checked) data.role.push('view');
            if (inputs[7].checked) data.role.push('edit');

            tableData.push(data);
        });

        // Отправляем данные на сервер для сохранения в menu.json
        fetch('/include/save-menu.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(tableData),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Изменения сохранены!');
                mainContainer.classList.remove('edit-mode');

                // Возвращаем кнопку "Изменить" в активное состояние
                editButton.disabled = false;
                editButton.style.backgroundColor = '#007BFF'; // Возвращаем исходный цвет

                // Отключаем кнопки "Сохранить" и "Добавить модуль"
                saveButton.disabled = true;
                addButton.disabled = true;
                saveButton.style.backgroundColor = '#ccc';
                addButton.style.backgroundColor = '#ccc';

                // Блокируем редактирование всех полей и чекбоксов
                const inputs = mainContainer.querySelectorAll('input:not([type="checkbox"])');
                const checkboxes = mainContainer.querySelectorAll('input[type="checkbox"]');

                inputs.forEach(input => {
                    input.readOnly = true; // Блокируем редактирование текстовых полей
                    input.style.backgroundColor = '#f9f9f9'; // Серый фон для неактивных полей
                    input.style.border = '1px solid #ddd'; // Серая рамка для неактивных полей
                });

                checkboxes.forEach(checkbox => {
                    checkbox.disabled = true; // Блокируем выбор чекбоксов
                });
            } else {
                alert('Ошибка при сохранении изменений.');
            }
        })
        .catch(error => {
            console.error('Ошибка:', error);
            alert('Ошибка при сохранении изменений.');
        });
    }
});

// Обработка кнопки "Добавить модуль"
addButton.addEventListener('click', (e) => {
    e.preventDefault();
    // Здесь можно добавить логику для добавления нового модуля
    alert('Добавление нового модуля...');
});