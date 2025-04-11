// Функция для сохранения данных пользователя
document.getElementById('saveButton').addEventListener('click', async function() {
    try {
        // Показываем индикатор загрузки
        showLoading(true);
        
        // Получаем данные из сессии
        const accessToken = '<?= $_SESSION["access_token"] ?? "" ?>';
        const userId = '<?= $_SESSION["userid"] ?? "" ?>';
        
        // Проверяем обязательные поля сессии
        if (!accessToken) {
            showErrorMessage('error', 'Ошибка', 'Требуется авторизация', 5000);
            showLoading(false);
            return;
        }
        
        if (!userId) {
            showErrorMessage('error', 'Ошибка', 'Не удалось определить пользователя', 5000);
            showLoading(false);
            return;
        }
        
        // Собираем данные с формы
        const formData = {
            email: document.getElementById('email').value.trim(),
            family: document.getElementById('lastName').value.trim(),
            full_name: document.getElementById('fullName').value.trim(),
            name: document.getElementById('firstName').value.trim(),
            telephone: document.getElementById('phone').value.trim(),
            tg_id: document.getElementById('telegramID').value.trim(),
            tg_username: document.getElementById('telegramUsername').value.trim()
        };
        
        // Валидация данных
        const validationErrors = [];
        
        // Email (обязательное поле)
        if (!formData.email) {
            validationErrors.push('Email - обязательное поле');
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
            validationErrors.push('Некорректный формат email');
        }
        
        // Полное имя (обязательное поле)
        if (!formData.full_name) {
            validationErrors.push('Полное имя - обязательное поле');
        } else if (!/^[а-яА-ЯёЁ\s-]{1,70}$/.test(formData.full_name)) {
            validationErrors.push('Полное имя должно содержать только русские буквы и быть не длиннее 70 символов');
        }
        
        // Фамилия (не обязательное)
        if (formData.family && !/^[а-яА-ЯёЁ\s-]{0,20}$/.test(formData.family)) {
            validationErrors.push('Фамилия должна содержать только русские буквы и быть не длиннее 20 символов');
        }
        
        // Имя (не обязательное)
        if (formData.name && !/^[а-яА-ЯёЁ\s-]{0,20}$/.test(formData.name)) {
            validationErrors.push('Имя должно содержать только русские буквы и быть не длиннее 20 символов');
        }
        
        // Телефон (не обязательное)
        if (formData.telephone) {
            if (!/^(\+7|8)[0-9]{10}$/.test(formData.telephone)) {
                validationErrors.push('Телефон должен начинаться с 8 или +7 и содержать 11 цифр');
            } else {
                // Нормализуем номер телефона
                formData.telephone = formData.telephone.replace(/^8/, '+7');
            }
        }
        
        // Telegram ID (не обязательное)
        if (formData.tg_id && !/^[0-9]{0,15}$/.test(formData.tg_id)) {
            validationErrors.push('Telegram ID должен содержать только цифры (максимум 15)');
        }
        
        // Telegram username (не обязательное)
        if (formData.tg_username && !/^[a-zA-Z0-9@_\-]{0,32}$/.test(formData.tg_username)) {
            validationErrors.push('Telegram username может содержать только латинские буквы, цифры и символы @, _, -');
        }
        
        // Если есть ошибки валидации - показываем их
        if (validationErrors.length > 0) {
            validationErrors.forEach(error => {
                showErrorMessage('warning', 'Внимание', error, 3000);
            });
            showLoading(false);
            return;
        }
        
        // Формируем данные для отправки
        const dataToSend = {
            access_token: accessToken,
            userid: userId,
            ...formData
        };
        
        // Определяем базовый URL
        const protocol = window.location.protocol;
        const host = window.location.hostname;
        const baseUrl = `${protocol}//${host}`;
        
        // Отправляем данные на сервер
        const response = await fetch(`${baseUrl}:5000/user/save`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(dataToSend)
        });
        
        const result = await response.json();
        
        if (!response.ok || !result.success) {
            const errorMessage = result.message || 'Не удалось сохранить данные';
            showErrorMessage('error', 'Ошибка', errorMessage, 5000);
            showLoading(false);
            return;
        }
        
        // Успешное сохранение
        showErrorMessage('success', 'Успех', 'Данные успешно сохранены', 3000);
        
    } catch (error) {
        console.error('Ошибка при сохранении данных:', error);
        showErrorMessage('error', 'Ошибка', 'Произошла ошибка при сохранении данных', 5000);
    } finally {
        showLoading(false);
    }
});

// Функция для показа/скрытия индикатора загрузки
function showLoading(show) {
    const loadingElement = document.getElementById('loading');
    if (loadingElement) {
        loadingElement.style.display = show ? 'flex' : 'none';
    }
}