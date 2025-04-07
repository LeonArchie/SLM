document.addEventListener('DOMContentLoaded', function() {
    // Элементы модального окна
    const addServerModal = document.getElementById('addServerModal');
    const closeModalBtn = document.querySelector('.close-modal');
    const cancelAddServerBtn = document.getElementById('cancelAddServer');
    const addServerForm = document.getElementById('addServerForm');
    const ipv6Checkbox = document.getElementById('ipv6Checkbox');
    const ipAddressInput = document.getElementById('ipAddress');
    const addServerButton = document.getElementById('AddServers');

    // Функция для проверки валидности формы
    function validateForm(form) {
        let isValid = true;
        Array.from(form.elements).forEach(element => {
            // Пропускаем кнопки и чекбокс IPv6
            if (element.type === 'submit' || element.type === 'button' || element.id === 'ipv6Checkbox') {
                return;
            }

            // Проверка обязательных полей
            if (element.required && !element.value) {
                element.classList.add('invalid');
                isValid = false;
            } 
            // Проверка по паттерну
            else if (element.type === 'text' && element.pattern && element.value) {
                const regex = new RegExp(element.pattern);
                if (!regex.test(element.value)) {
                    element.classList.add('invalid');
                    isValid = false;
                }
            }
        });
        return isValid;
    }

    // Функция для сброса ошибок валидации
    function resetValidationErrors(form) {
        Array.from(form.elements).forEach(element => {
            element.classList.remove('invalid');
        });
    }

    // Функция для закрытия модального окна
    function closeModal() {
        addServerModal.style.display = 'none';
        addServerForm.reset();
        resetValidationErrors(addServerForm);
    }

    // Обработчик для кнопки "Добавить оборудование"
    if (addServerButton) {
        addServerButton.addEventListener('click', function() {
            addServerModal.style.display = 'block';
            resetValidationErrors(addServerForm);
        });
    }

    // Закрытие модального окна
    if (closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
    if (cancelAddServerBtn) cancelAddServerBtn.addEventListener('click', closeModal);

    // Закрытие при клике вне модального окна
    window.addEventListener('click', function(event) {
        if (event.target === addServerModal) {
            closeModal();
        }
    });

    // Обработчик изменения чекбокса IPv6
    if (ipv6Checkbox && ipAddressInput) {
        ipv6Checkbox.addEventListener('change', function() {
            if (this.checked) {
                ipAddressInput.pattern = '^([0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}$';
                ipAddressInput.title = 'Формат IPv6: xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx';
                ipAddressInput.maxLength = 45;
            } else {
                ipAddressInput.pattern = '^([0-9]{1,3}\.){3}[0-9]{1,3}$';
                ipAddressInput.title = 'Формат IPv4: xxx.xxx.xxx.xxx';
                ipAddressInput.maxLength = 16;
            }
            ipAddressInput.value = '';
            ipAddressInput.classList.remove('invalid');
        });
    }

    // Валидация полей при вводе
    if (addServerForm) {
        Array.from(addServerForm.elements).forEach(element => {
            if (element.tagName === 'INPUT' || element.tagName === 'SELECT') {
                element.addEventListener('input', function() {
                    this.classList.remove('invalid');
                });
            }
        });
    }

     // Обработчик отправки формы
     if (addServerForm) {
        addServerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            resetValidationErrors(this);
            
            if (!validateForm(this)) {
                showErrorMessage('warning', 'Внимание', 'Пожалуйста, заполните все обязательные поля корректно.', 3000);
                return;
            }
            
            // Отключаем кнопку отправки
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Создание...';
            
            // Собираем данные формы
            const formData = {
                name: document.getElementById('serverName').value.trim(),
                status: document.getElementById('serverStatus').value,
                ipv6: ipv6Checkbox.checked,
                ipAddress: ipAddressInput.value.trim(),
                domain: document.getElementById('domain').value.trim(),
            };
            
            // Отправляем AJAX запрос
            fetch('back/ServerHardvare/CreateServer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(async response => {
                // Проверяем статус ответа
                if (!response.ok) {
                    let errorText = await response.text();
                    throw new Error(`HTTP ${response.status}: ${errorText || 'No error details'}`);
                }
                
                // Пытаемся распарсить JSON
                try {
                    return await response.json();
                } catch (e) {
                    throw new Error('Invalid JSON response from server');
                }
            })
            .then(data => {
                if (!data) {
                    throw new Error('Empty response from server');
                }
                
                if (data.success) {
                    showErrorMessage('success', 'Успех', 'Оборудование успешно добавлено.', 3000);
                    closeModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    throw new Error(data.error || 'Неизвестная ошибка сервера');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                let errorMessage = error.message;
                
                // Упрощаем сообщение для пользователя
                if (errorMessage.includes('Invalid JSON') || errorMessage.includes('Empty response')) {
                    errorMessage = 'Сервер вернул некорректный ответ. Пожалуйста, попробуйте позже.';
                }
                
                showErrorMessage('error', 'Ошибка', 'Не удалось добавить оборудование: ' + errorMessage, 5000);
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Создать';
            });
        });
    }
});