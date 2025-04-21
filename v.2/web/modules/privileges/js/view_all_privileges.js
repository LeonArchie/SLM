document.addEventListener('DOMContentLoaded', function() {
    const viewAllPrivilegesForm = document.getElementById('viewAllPrivilegesForm');
    const privilegesTableBody = document.getElementById('privilegesTableBody');
    const closeButton = document.getElementById('closeViewAllPrivilegesForm');
    const viewAllButton = document.getElementById('ViewAllPrivileges');
    const loading = document.getElementById('loading');
    
    if (!viewAllPrivilegesForm || !privilegesTableBody || !closeButton || !viewAllButton) {
        console.error('Не найдены необходимые элементы DOM');
        return;
    }
    
    viewAllButton.addEventListener('click', async function() {
        viewAllPrivilegesForm.style.display = 'block';
        await fetchAndDisplayPrivileges();
    });
    
    closeButton.addEventListener('click', function() {
        viewAllPrivilegesForm.style.display = 'none';
    });
    
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
            const apiUrl = `${baseUrl}:5000/privileges/get-all`;
            
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
            
            renderPrivilegesTable(data.privileges);
            
        } catch (error) {
            console.error('Error fetching privileges:', error);
            showErrorMessage('error', 'Ошибка', error.message || 'Не удалось загрузить данные.', 5000);
        } finally {
            if (loading) loading.style.display = 'none';
        }
    }
    
    function renderPrivilegesTable(privileges) {
        privilegesTableBody.innerHTML = '';

        if (!privileges || privileges.length === 0) {
            privilegesTableBody.innerHTML = `
                <tr>
                    <td colspan="2" style="text-align: center; padding: 20px; color: #7f8c8d;">
                        Нет данных о полномочиях
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