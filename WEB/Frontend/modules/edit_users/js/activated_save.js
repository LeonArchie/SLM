document.addEventListener("DOMContentLoaded", function () {
    const saveButton = document.getElementById("saveButton");
    const formContainer = document.querySelector(".scrollable-form"); // Изменено с form на .scrollable-form
    
    const initialState = {};

    function captureInitialState() {
        const inputs = formContainer.querySelectorAll("input:not([type='hidden']), textarea, select");
        inputs.forEach(input => {
            if (input.type === "checkbox" || input.type === "radio") {
                initialState[input.id] = input.checked; // Используем id вместо name для большей надежности
            } else if (!input.readOnly) { // Игнорируем поля только для чтения
                initialState[input.id] = input.value;
            }
        });
    }

    function isFormChanged() {
        const inputs = formContainer.querySelectorAll("input:not([type='hidden']), textarea, select");
        for (const input of inputs) {
            if (input.readOnly) continue; // Пропускаем поля только для чтения
            
            if (input.type === "checkbox" || input.type === "radio") {
                if (initialState[input.id] !== input.checked) {
                    return true;
                }
            } else {
                if (initialState[input.id] !== input.value) {
                    return true;
                }
            }
        }
        return false;
    }

    function handleFormChange() {
        saveButton.disabled = !isFormChanged();
    }

    // Инициализация
    captureInitialState();
    saveButton.disabled = true; // Начальное состояние - отключено

    // Добавляем обработчики событий
    const inputs = formContainer.querySelectorAll("input:not([type='hidden']), textarea, select");
    inputs.forEach(input => {
        if (input.readOnly) return; // Не добавляем обработчики для полей только для чтения
        
        if (input.type === "checkbox" || input.type === "radio") {
            input.addEventListener("change", handleFormChange);
        } else {
            input.addEventListener("input", handleFormChange);
        }
    });
});