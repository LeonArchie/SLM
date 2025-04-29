document.addEventListener('DOMContentLoaded', function() {
    // Обработчик клика на кнопку "Сменить статус пользователя"
    document.getElementById('blockButton')?.addEventListener('click', function() {
        const selectedUsers = getSelectedUsers();
        if (selectedUsers.length === 0) {
            showErrorMessage('warning', 'Внимание', 'Выберите хотя бы одного пользователя.', 3000);
            return;
        }
        showConfirmationModal(selectedUsers);
    });
});

// Функция показа модального окна подтверждения
function showConfirmationModal(selectedUsers) {
    // Удаляем предыдущее модальное окно, если оно есть
    const oldModal = document.querySelector('.modal.confirmation-modal');
    if (oldModal) oldModal.remove();

    // Создаем новое модальное окно
    const modal = document.createElement('div');
    modal.className = 'modal confirmation-modal';
    modal.style.display = 'block'; // Принудительно показываем окно
    modal.style.opacity = '0'; // Начальное состояние для анимации
    
    modal.innerHTML = `
        <div class="modal-content" style="transform: translateY(-20px)">
            <div class="modal-header">
                <h2>Подтверждение</h2>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <p>Вы уверены, что хотите изменить статус ${selectedUsers.length} пользователя(ей)?</p>
                <div class="form-actions">
                    <button class="cancel-button">Отмена</button>
                    <button class="submit-button">Подтвердить</button>
                </div>
            </div>
        </div>
    `;

    // Добавляем модальное окно в DOM
    document.body.appendChild(modal);
    
    // Запускаем анимацию появления
    setTimeout(() => {
        modal.style.opacity = '1';
        modal.querySelector('.modal-content').style.transform = 'translateY(0)';
    }, 10);

    // Обработчики событий
    modal.querySelector('.close-modal').addEventListener('click', () => closeModal(modal));
    modal.querySelector('.cancel-button').addEventListener('click', () => closeModal(modal));
    modal.querySelector('.submit-button').addEventListener('click', () => {
        closeModal(modal);
        handleBlockUsers(selectedUsers);
    });
}

// Функция закрытия модального окна
function closeModal(modal) {
    modal.style.opacity = '0';
    modal.querySelector('.modal-content').style.transform = 'translateY(-20px)';
    setTimeout(() => {
        modal.style.display = 'none';
        modal.remove();
    }, 300);
}

// Основная функция обработки блокировки пользователей
async function handleBlockUsers(selectedUsers) {
    try {
        // 1. Получаем и проверяем авторизационные данные
        const { accessToken, currentUserId } = getAuthData();
        if (!accessToken || !currentUserId) {
            showAuthError();
            return;
        }

        // 2. Формируем URL API
        const apiUrl = buildApiUrl();

        // 3. Выполняем запросы
        showProcessingMessage();
        const results = await processBlockRequests(apiUrl, accessToken, currentUserId, selectedUsers);

        // 4. Обрабатываем результаты
        handleResults(results, selectedUsers);

    } catch (error) {
        handleUnexpectedError(error);
    }
}

// Вспомогательные функции

function getAuthData() {
    return {
        accessToken: localStorage.getItem('access_token'),
        currentUserId: localStorage.getItem('user_id')
    };
}

function showAuthError() {
    showErrorMessage('error', 'Ошибка авторизации', 
        'Требуется авторизация. Пожалуйста, войдите снова.', 5000);
}

function getSelectedUsers() {
    return Array.from(document.querySelectorAll('.userCheckbox:checked'))
        .map(checkbox => checkbox.dataset.userid);
}

function buildApiUrl() {
    const protocol = window.location.protocol;
    const host = window.location.host.replace(/:80|:443/g, '');
    return `${protocol}//${host}:5000/setting/user/block`;
}

function showProcessingMessage() {
    showErrorMessage('info', 'Выполнение', 'Идет обновление статусов пользователей...', 2000);
}

async function processBlockRequests(apiUrl, accessToken, currentUserId, userIds) {
    return await Promise.allSettled(
        userIds.map(userId => 
            sendBlockRequest(apiUrl, accessToken, currentUserId, userId)
        )
    );
}

async function sendBlockRequest(apiUrl, accessToken, requestingUserId, blockUserId) {
    try {
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${accessToken}`
            },
            body: JSON.stringify({
                access_token: accessToken,
                user_id: requestingUserId,
                block_user_id: blockUserId
            })
        });

        const responseData = await parseResponse(response);
        return validateResponse(responseData, blockUserId);

    } catch (error) {
        console.error(`Block request failed for user ${blockUserId}:`, error);
        throw error;
    }
}

async function parseResponse(response) {
    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.error || `HTTP error: ${response.status}`);
    }
    return await response.json();
}

function validateResponse(data, blockUserId) {
    const userResult = Array.isArray(data.results) ? 
        data.results.find(r => r.user_id === blockUserId) : null;

    if (!userResult) throw new Error('Не найден результат для пользователя');
    if (!userResult.success) throw new Error(userResult.message || 'Операция не выполнена');

    return {
        success: true,
        user_id: blockUserId,
        new_status: userResult.new_status,
        message: userResult.message
    };
}

function handleResults(results, selectedUsers) {
    const { successCount, errorMessages } = processResults(results, selectedUsers);
    showResultMessage(successCount, errorMessages.length, errorMessages);
}

function processResults(results, selectedUsers) {
    let successCount = 0;
    const errorMessages = [];

    results.forEach((result, index) => {
        const userId = selectedUsers[index];
        
        if (result.status === 'fulfilled' && result.value.success) {
            updateUserRow(userId, result.value.new_status);
            successCount++;
        } else {
            const errorMsg = result.reason?.message || result.value?.message || 'Неизвестная ошибка';
            errorMessages.push(`Пользователь ${userId}: ${errorMsg}`);
        }
    });

    return { successCount, errorMessages };
}

function showResultMessage(successCount, errorCount, errorMessages) {
    if (successCount > 0 && errorCount === 0) {
        showSuccessMessage(successCount);
    } else if (successCount > 0 && errorCount > 0) {
        showPartialSuccessMessage(successCount, errorCount, errorMessages);
    } else {
        showFailureMessage(errorMessages);
    }
}

function showSuccessMessage(count) {
    showErrorMessage('success', 'Успех', `Статусы ${count} пользователей успешно обновлены.`, 3000);
}

function showPartialSuccessMessage(successCount, errorCount, errorMessages) {
    const message = [
        `Статусы ${successCount} пользователей обновлены.`,
        `Не удалось обновить ${errorCount}:`,
        ...errorMessages
    ].join('\n');
    showErrorMessage('warning', 'Результат', message, 5000);
}

function showFailureMessage(errorMessages) {
    showErrorMessage('error', 'Ошибка', 
        `Не удалось обновить статусы:\n${errorMessages.join('\n')}`, 
        5000);
}

function handleUnexpectedError(error) {
    console.error('Unexpected error:', error);
    showErrorMessage('error', 'Ошибка', 
        'Произошла непредвиденная ошибка: ' + (error.message || ''), 
        5000);
}

function updateUserRow(userId, newStatus) {
    try {
        const row = document.querySelector(`.userCheckbox[data-userid="${userId}"]`)?.closest('tr');
        if (!row) return;

        const statusCheckbox = row.querySelector('.status-indicator');
        if (statusCheckbox) {
            statusCheckbox.checked = newStatus;
            highlightRow(row);
        }
    } catch (error) {
        console.error(`Row update error for user ${userId}:`, error);
    }
}

function highlightRow(row) {
    row.classList.add('updated-row');
    setTimeout(() => row.classList.remove('updated-row'), 2000);
}