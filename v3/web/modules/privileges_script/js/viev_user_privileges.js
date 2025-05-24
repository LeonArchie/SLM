document.addEventListener('DOMContentLoaded', function () {
    // DOM элементы
    const viewPrivilegesForm = document.getElementById('viewPrivilegesForm');
    const userPrivilegesTableBody = document.getElementById('userPrivilegesTableBody');
    const closeButton = document.getElementById('closeViewPrivilegesForm');
    const viewButton = document.getElementById('VievPrivileges');
    const userIdInput = document.getElementById('userIDView');
    const loading = document.getElementById('loading');

    // Проверка необходимых элементов
    if (!viewPrivilegesForm || !userPrivilegesTableBody || !closeButton || !viewButton) {
        console.error('Не найдены необходимые элементы DOM');
        return;
    }

    // Данные привилегий
    let userPrivileges = [];

    // Инициализация
    initViewPrivilegesButton();

    // Обработчики событий
    closeButton.addEventListener('click', function () {
        viewPrivilegesForm.style.display = 'none';
        userPrivileges = [];
        renderUserPrivilegesTable([]);
    });

    // Добавляем обработчик поиска
    const privilegesUserSearch = document.getElementById('privilegesUserSearch');
    if (privilegesUserSearch) {
        privilegesUserSearch.addEventListener('input', function() {
            filterUserPrivileges(this.value.toLowerCase());
        });
    }

    // Функция фильтрации привилегий
    function filterUserPrivileges(searchTerm) {
        const rows = userPrivilegesTableBody.querySelectorAll('tr');
        
        rows.forEach(row => {
            const id = row.cells[0].textContent.toLowerCase();
            const name = row.cells[1].textContent.toLowerCase();
            
            if (id.includes(searchTerm) || name.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Основные функции
    function initViewPrivilegesButton() {
        const viewButton = document.getElementById('VievPrivileges');
        if (!viewButton) return;

        // Обновляем состояние кнопки при изменении выбора
        document.addEventListener('change', function (e) {
            if (e.target.classList.contains('userCheckbox')) {
                updateViewButtonState();
            }
        });

        // Обработка клика по кнопке
        viewButton.addEventListener('click', async function () {
            const selectedUserId = getSelectedUserId();
            if (!selectedUserId) {
                showErrorMessage('error', 'Ошибка', 'Пользователь не выбран', 3000);
                return;
            }

            userIdInput.value = selectedUserId;
            viewPrivilegesForm.style.display = 'flex';
            await fetchAndDisplayUserPrivileges(selectedUserId);
        });

        // Первоначальное обновление состояния
        updateViewButtonState();
    }

    function updateViewButtonState() {
        const selectedCount = document.querySelectorAll('.userCheckbox:checked').length;
        const viewButton = document.getElementById('VievPrivileges');
        if (viewButton) {
            viewButton.disabled = selectedCount !== 1;
        }
    }

    function getSelectedUserId() {
        const selectedCheckbox = document.querySelector('.userCheckbox:checked');
        if (!selectedCheckbox) return null;
        return selectedCheckbox.dataset.userid;
    }

    async function fetchAndDisplayUserPrivileges(userId) {
        try {
            if (loading) loading.style.display = 'flex';

            const accessToken = localStorage.getItem('access_token');
            const currentUserId = localStorage.getItem('user_id');

            if (!accessToken || !currentUserId) {
                throw new Error('Необходима авторизация');
            }

            const protocol = window.location.protocol;
            const host = window.location.hostname;
            const port = window.location.port ? `:${window.location.port}` : '';
            const baseUrl = `${protocol}//${host}${port}`;
            const apiUrl = `${baseUrl}:5000/privileges/scripts/user_view`;

            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    access_token: accessToken,
                    user_id: userId
                })
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Ошибка сервера');
            }

            const data = await response.json();

            // Проверка на наличие поля scripts
            if (!data.scripts) {
                throw new Error('Некорректный формат данных');
            }

            // Преобразуем данные для отображения
            const privilegesForDisplay = data.scripts.map(script => ({
                id_privilege: script.guid_scripts,
                name_privilege: script.name_scripts
            }));

            renderUserPrivilegesTable(privilegesForDisplay);

        } catch (error) {
            console.error('Error fetching user privileges:', error);
            showErrorMessage('error', 'Ошибка', error.message || 'Не удалось загрузить данные.', 5000);
            renderUserPrivilegesTable([]);
        } finally {
            if (loading) loading.style.display = 'none';
        }
    }

    function renderUserPrivilegesTable(privileges) {
        userPrivilegesTableBody.innerHTML = '';

        if (!privileges || privileges.length === 0) {
            userPrivilegesTableBody.innerHTML = `
                <tr>
                    <td colspan="2" style="text-align: center; padding: 20px; color: #7f8c8d;">
                        ${privileges.length === 0 ? 'Разрешения отсутствуют' : 'Данные не загружены'}
                    </td>
                </tr>
            `;
            return;
        }

        privileges.forEach(privilege => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${privilege.id_privilege || 'N/A'}</td>
                <td>${privilege.name_privilege || 'Без названия'}</td>
            `;
            userPrivilegesTableBody.appendChild(row);
        });
    }
});