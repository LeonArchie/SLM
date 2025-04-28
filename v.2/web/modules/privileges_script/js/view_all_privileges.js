document.addEventListener('DOMContentLoaded', function() {
    // DOM элементы
    const viewAllPrivilegesForm = document.getElementById('viewAllPrivilegesForm');
    const privilegesTableBody = document.getElementById('privilegesTableBody');
    const closeButton = document.getElementById('closeViewAllPrivilegesForm');
    const viewAllButton = document.getElementById('ViewAllPrivileges');
    const privilegesSearch = document.getElementById('privilegesSearch');
    const loading = document.getElementById('loading');
    
    // Проверка необходимых элементов
    if (!viewAllPrivilegesForm || !privilegesTableBody || !closeButton || !viewAllButton) {
        console.error('Не найдены необходимые элементы DOM');
        return;
    }
    
    // Данные привилегий
    let allPrivileges = [];
    
    // Обработчики событий
    viewAllButton.addEventListener('click', async function() {
        viewAllPrivilegesForm.style.display = 'block';
        await fetchAndDisplayPrivileges();
    });
    
    closeButton.addEventListener('click', function() {
        viewAllPrivilegesForm.style.display = 'none';
        if (privilegesSearch) privilegesSearch.value = '';
        renderPrivilegesTable(allPrivileges);
    });
    
    if (privilegesSearch) {
        privilegesSearch.addEventListener('input', function() {
            filterPrivileges(this.value.trim().toLowerCase());
        });
    }
    
    // Основные функции
    async function fetchAndDisplayPrivileges() {
        try {
            if (loading) loading.style.display = 'flex';
            
            const accessToken = localStorage.getItem('access_token');
            const userId = localStorage.getItem('user_id');
            
            if (!accessToken || !userId) {
                throw new Error('Необходима авторизация');
            }
            
            const protocol = window.location.protocol;
            const host = window.location.hostname;
            const port = window.location.port ? `:${window.location.port}` : '';
            const baseUrl = `${protocol}//${host}${port}`;
            const apiUrl = `${baseUrl}:5000/privileges/scripts/get-all`;
            
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
                throw new Error(errorData.error || 'Ошибка сервера');
            }
            
            const data = await response.json();
            
            if (data.status !== 'success') {
                throw new Error(data.error || 'Не удалось получить данные');
            }
            
            allPrivileges = data.privileges || [];
            renderPrivilegesTable(allPrivileges);
            
        } catch (error) {
            console.error('Error fetching privileges:', error);
            showErrorMessage('error', 'Ошибка', error.message || 'Не удалось загрузить данные.', 5000);
            renderPrivilegesTable([]);
        } finally {
            if (loading) loading.style.display = 'none';
        }
    }
    
    function filterPrivileges(searchTerm) {
        if (!searchTerm) {
            renderPrivilegesTable(allPrivileges);
            return;
        }

        const filtered = allPrivileges.filter(privilege => {
            const nameMatch = privilege.name_privileges && 
                privilege.name_privileges.toLowerCase().includes(searchTerm);
            const idMatch = privilege.id_privileges && 
                privilege.id_privileges.toLowerCase().includes(searchTerm);
            return nameMatch || idMatch;
        });

        renderPrivilegesTable(filtered);
    }
    
    function renderPrivilegesTable(privileges) {
        privilegesTableBody.innerHTML = '';

        if (!privileges || privileges.length === 0) {
            privilegesTableBody.innerHTML = `
                <tr>
                    <td colspan="2" style="text-align: center; padding: 20px; color: #7f8c8d;">
                        ${allPrivileges.length === 0 ? 'Данные не загружены' : 'Ничего не найдено'}
                    </td>
                </tr>
            `;
            return;
        }

        privileges.forEach(privilege => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${privilege.id_privileges || 'N/A'}</td>
                <td>${privilege.name_privileges || 'Без названия'}</td>
            `;
            privilegesTableBody.appendChild(row);
        });
    }
});