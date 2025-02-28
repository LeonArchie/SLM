document.addEventListener('DOMContentLoaded', function () {
    const fields = {
        lastName: { maxLength: 20, regex: /^[а-яА-ЯёЁ]+$/ },
        firstName: { maxLength: 20, regex: /^[а-яА-ЯёЁ]+$/ },
        fullName: { maxLength: 50, regex: /^[а-яА-ЯёЁ\s]+$/ },
        telegramUsername: { maxLength: 20, regex: /^@[a-zA-Z0-9_]+$/ },
        telegramID: { maxLength: 15, regex: /^\d+$/ },
        email: { regex: /^[^\s@]+@[^\s@]+\.[^\s@]+$/ },
        phone: { regex: /^\+7\d{10}$/, maxLength: 12 } // Обновленное правило для телефона
    };

    function validateField(fieldId, rules) {
        const field = document.getElementById(fieldId);
        if (!field) return;

        field.addEventListener('blur', function () {
            let isValid = true;
            const value = field.value.trim();

            if (rules.maxLength && value.length > rules.maxLength) {
                isValid = false;
            }

            if (rules.regex && !rules.regex.test(value)) {
                isValid = false;
            }

            if (!isValid) {
                field.style.borderColor = 'red';
            } else {
                field.style.borderColor = '';
            }
        });
    }

    // Валидация телефонного номера
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        // Добавляем обработчик для события input
        phoneInput.addEventListener('input', function () {
            let rawValue = phoneInput.value.replace(/\D/g, ''); // Удаляем все нецифровые символы

            // Если первый символ "8", заменяем его на "7"
            if (rawValue.startsWith('8') && rawValue.length > 0) {
                rawValue = '7' + rawValue.slice(1);
            }

            // Ограничиваем длину номера до 11 символов (включая "+7")
            if (rawValue.length > 10) rawValue = rawValue.slice(0, 10);

            // Форматируем номер как +7XXXXXXXXXX
            phoneInput.value = rawValue ? `+7${rawValue}` : '';
        });

        // Добавляем обработчик для события blur
        phoneInput.addEventListener('blur', function () {
            const value = phoneInput.value.trim();
            if (!fields.phone.regex.test(value)) {
                phoneInput.style.borderColor = 'red';
            } else {
                phoneInput.style.borderColor = '';
            }
        });
    }

    // Инициализация валидации для всех полей
    for (const [fieldId, rules] of Object.entries(fields)) {
        if (fieldId !== 'phone') {
            validateField(fieldId, rules);
        }
    }
});