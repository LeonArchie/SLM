document.addEventListener('DOMContentLoaded', function() {
    const editButton = document.getElementById('EditButton');
    const saveButton = document.getElementById('SaveButton');
    let editMode = false;
    
    // Обработчик кнопки редактирования
    editButton.addEventListener('click', function() {
        editMode = true;
        editButton.disabled = true;
        saveButton.disabled = false;
        
        // Активируем редактируемые поля
        document.querySelectorAll('.editable').forEach(el => {
            el.contentEditable = true;
            el.classList.add('editing');
        });
    });
    
// Обработчик кнопки сохранения
saveButton.addEventListener('click', function() {
    // Собираем данные для сохранения
    const modules = buildModuleStructure();
    
    // Формируем полную структуру данных
    const data = {
        menu: modules,
        source: 'save_button'
    };
    
    fetch('back/modules/save_modules.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) throw new Error('Ошибка сети');
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showErrorMessage('success', 'Успех', 'Изменения успешно сохранены', 5000);
            
            // Выходим из режима редактирования только при успешном сохранении
            editMode = false;
            editButton.disabled = false;
            saveButton.disabled = true;
            
            // Деактивируем редактируемые поля
            document.querySelectorAll('.editable').forEach(el => {
                el.contentEditable = false;
                el.classList.remove('editing');
            });
        } else {
            showErrorMessage('error', 'Ошибка', 'Ошибка 0057: ' + (data.message || 'Не удалось сохранить изменения'), 5000);
            
            // Если keepEditMode = true, остаемся в режиме редактирования
            if (data.keepEditMode) {
                editMode = true;
                editButton.disabled = true;
                saveButton.disabled = false;
                
                document.querySelectorAll('.editable').forEach(el => {
                    el.contentEditable = true;
                    el.classList.add('editing');
                });
            }
        }
    })
    .catch(error => {
        showErrorMessage('error', 'Ошибка', 'Ошибка 0058: Ошибка сети: ' + error.message, 5000);
        
        // Остаемся в режиме редактирования при ошибке сети
        editMode = true;
        editButton.disabled = true;
        saveButton.disabled = false;
        
        document.querySelectorAll('.editable').forEach(el => {
            el.contentEditable = true;
            el.classList.add('editing');
        });
    });
});
    
    // Функция сохранения изменений
    function saveChanges() {
        const modules = buildModuleStructure();
        
        // Формируем полную структуру данных
        const data = {
            menu: modules
        };
        
        fetch('/save_modules.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showErrorMessage('success', 'Успех', 'Изменения успешно сохранены', 5000);
            } else {
                showErrorMessage('error', 'Ошибка', 'Ошибка 0057: Не удалось сохранить изменения: ' + (data.message || 'Неизвестная ошибка'), 5000);
            }
        })
        .catch(error => {
            showErrorMessage('error', 'Ошибка', 'Ошибка 0058: Ошибка сети: ' + error.message, 5000);
        });
    }

    // Новая функция для построения правильной структуры модулей
    function buildModuleStructure() {
        const modules = [];
        const rows = document.querySelectorAll('#modulesTable tbody tr');
        const moduleMap = new Map();
        
        // Сначала создаем все модули
        rows.forEach(row => {
            const guid = row.dataset.guid;
            const level = parseInt(row.dataset.level);
            const title = row.querySelector('[data-field="title"]').textContent;
            const url = row.querySelector('[data-field="url"]').textContent;
            const icon = row.querySelector('[data-field="icon"]').textContent;
            const active = row.querySelector('.active-checkbox').checked;
            
            const module = {
                guid,
                title,
                url,
                icon,
                active,
                role: ["view"],
                dropdown: []
            };
            
            moduleMap.set(guid, module);
        });
        
        // Затем строим иерархию
        rows.forEach(row => {
            const guid = row.dataset.guid;
            const level = parseInt(row.dataset.level);
            const module = moduleMap.get(guid);
            
            if (level === 0) {
                modules.push(module);
            } else {
                // Находим родителя
                let parentRow = row.previousElementSibling;
                while (parentRow) {
                    const parentLevel = parseInt(parentRow.dataset.level);
                    if (parentLevel === level - 1) {
                        const parentGuid = parentRow.dataset.guid;
                        const parentModule = moduleMap.get(parentGuid);
                        if (parentModule) {
                            parentModule.dropdown.push(module);
                        }
                        break;
                    }
                    parentRow = parentRow.previousElementSibling;
                }
            }
        });
        
        return modules;
    }
});