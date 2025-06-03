document.addEventListener('DOMContentLoaded', function() {
    console.log('Скрипт save_button.js загружен');

    // Проверка и создание fallback для showErrorMessage
    if (typeof window.showErrorMessage !== 'function') {
        window.showErrorMessage = function(type, title, message, duration) {
            console.log(`[${type}] ${title}: ${message}`);
            alert(`${title}\n${message}`);
        };
    }

    const saveButton = document.getElementById('saveButton');
    if (!saveButton) {
        showErrorMessage('error', 'Ошибка', 'Кнопка сохранения не найдена', 5000);
        return;
    }

    // Конфигурация валидации полей
    const validationRules = {
        userlogin: {
            id: 'login',
            required: true,
            pattern: /^[a-zA-Z0-9_\-@.]+$/,
            error: 'Только латиница, цифры и спецсимволы'
        },
        full_name: {
            id: 'fullName',
            required: true,
            pattern: /^[а-яА-ЯёЁ\s-]{1,70}$/,
            error: 'Только русские буквы и пробелы (макс. 70)'
        },
        user_off_email: {
            id: 'user_off_email',
            required: true,
            pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
            error: 'Некорректный формат email'
        },
        name: {
            id: 'firstName',
            pattern: /^[а-яА-ЯёЁ]{0,20}$/,
            error: 'Только русские буквы (макс. 20)'
        },
        family: {
            id: 'lastName',
            pattern: /^[а-яА-ЯёЁ]{0,20}$/,
            error: 'Только русские буквы (макс. 20)'
        },
        department: {
            id: 'department',
            pattern: /^[а-яА-ЯёЁ\s-.,()]{0,100}$/,
            error: 'Только русские буквы и спецсимволы (макс. 100)'
        },
        post: {
            id: 'post',
            pattern: /^[а-яА-ЯёЁ\s-.,()]{0,100}$/,
            error: 'Только русские буквы и спецсимволы (макс. 100)'
        },
        personal_mail: {
            id: 'personal_mail',
            pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
            error: 'Некорректный формат email'
        },
        corp_phone: {
            id: 'corp_phone',
            pattern: /^(\+7|8)\d{10}$/,
            normalize: value => value.replace(/^8/, '+7'),
            error: 'Формат: +79991234567 или 89991234567'
        },
        telephone: {
            id: 'telephone',
            pattern: /^(\+7|8)\d{10}$/,
            normalize: value => value.replace(/^8/, '+7'),
            error: 'Формат: +79991234567 или 89991234567'
        },
        tg_id: {
            id: 'telegramID',
            pattern: /^\d{0,15}$/,
            error: 'Только цифры (макс. 15)'
        },
        tg_username: {
            id: 'telegramUsername',
            pattern: /^[a-zA-Z0-9@_\-]{0,32}$/,
            error: 'Латиница, цифры, @, _, - (макс. 32)'
        }
    };

    saveButton.addEventListener('click', async function(e) {
        e.preventDefault();
        console.log('Кнопка "Сохранить" нажата');

        try {
            // Валидация полей
            const errors = validateFields();
            if (errors.length > 0) {
                showErrorMessage('warning', 'Ошибка валидации', errors.join('<br>'), 5000);
                return;
            }

            // Получение данных
            const accessToken = localStorage.getItem('access_token');
            const adminId = localStorage.getItem('user_id');
            const userId = document.getElementById('userID').value;

            if (!accessToken || !adminId || !userId) {
                throw new Error('Недостаточно данных для запроса');
            }

            // Формирование данных
            const userData = {
                corp_phone: normalizeValue('corp_phone'),
                department: getValue('department'),
                family: getValue('lastName'),
                full_name: getValue('fullName'),
                name: getValue('firstName'),
                personal_mail: getValue('personal_mail'),
                post: getValue('post'),
                telephone: normalizeValue('telephone'),
                tg_id: getValue('telegramID'),
                tg_username: getValue('telegramUsername'),
                user_off_email: getValue('user_off_email'),
                userlogin: getValue('login'),
                visible_corp_phone: document.getElementById('visible_corp_phone').checked,
                visible_personal_mail: document.getElementById('visible_personal_mail').checked,
                visible_telephone: document.getElementById('visible_telephone').checked
            };

            // Отправка запроса
            showLoading(true);
            const response = await fetch(`${window.location.protocol}//${window.location.host.replace(/:80|:443/g, '')}:5000/setting/user/full_update`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    access_token: accessToken,
                    user_admin_id: adminId,
                    user_update_id: userId,
                    user_data: userData
                })
            });

            const result = await response.json();
            showLoading(false);

            if (!response.ok) {
                throw new Error(result.error || 'Ошибка сервера');
            }

            showErrorMessage('success', 'Успех', 'Данные успешно сохранены', 3000);
            setTimeout(() => window.location.reload(), 2000);

        } catch (error) {
            console.error('Ошибка при сохранении:', error);
            showLoading(false);
            showErrorMessage('error', 'Ошибка', 'Не удалось сохранить данные: ' + error.message, 5000);
        }
    });

    // Функция валидации полей
    function validateFields() {
        const errors = [];
        const requiredFields = ['userlogin', 'full_name', 'user_off_email'];
        
        // Проверка обязательных полей
        for (const field of requiredFields) {
            const rule = validationRules[field];
            const element = document.getElementById(rule.id);
            const value = element.value.trim();
            
            if (!value) {
                const fieldName = element.labels[0]?.textContent || rule.id;
                errors.push(`Поле "${fieldName}" обязательно для заполнения`);
                element.classList.add('invalid-field');
            }
        }
        
        // Проверка всех полей по правилам
        for (const [field, rule] of Object.entries(validationRules)) {
            const element = document.getElementById(rule.id);
            if (!element) continue;
            
            const value = element.value.trim();
            
            // Пропускаем пустые необязательные поля
            if (!value && !rule.required) continue;
            
            // Проверка по регулярному выражению
            if (rule.pattern && !rule.pattern.test(value)) {
                errors.push(`${element.labels[0]?.textContent || rule.id}: ${rule.error}`);
                element.classList.add('invalid-field');
            } else if (value) {
                element.classList.remove('invalid-field');
            }
        }
        
        return errors;
    }

    // Функция получения значения поля
    function getValue(id) {
        const element = document.getElementById(id);
        return element ? element.value.trim() || null : null;
    }

    // Функция нормализации телефонов
    function normalizeValue(id) {
        const rule = Object.values(validationRules).find(r => r.id === id);
        if (!rule || !rule.normalize) return getValue(id);
        
        const element = document.getElementById(id);
        if (!element) return null;
        
        const value = element.value.trim();
        return value ? rule.normalize(value) : null;
    }

    // Функция показа/скрытия лоадера
    function showLoading(show) {
        const loadingElement = document.querySelector('.loading-container');
        if (loadingElement) {
            loadingElement.style.display = show ? 'flex' : 'none';
        }
    }
});