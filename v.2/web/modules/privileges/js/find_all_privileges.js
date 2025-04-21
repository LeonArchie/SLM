document.addEventListener('DOMContentLoaded', function() {
    const privilegesSearch = document.getElementById('privilegesSearch');
    const privilegesTableBody = document.getElementById('privilegesTableBody');
    let allPrivileges = [];

    if (privilegesSearch) {
        privilegesSearch.addEventListener('input', function() {
            filterPrivileges(this.value.trim().toLowerCase());
        });
    }

    function filterPrivileges(searchTerm) {
        if (!searchTerm) {
            renderPrivileges(allPrivileges);
            return;
        }

        const filtered = allPrivileges.filter(privilege => {
            return (privilege.name_privileges && privilege.name_privileges.toLowerCase().includes(searchTerm)) ||
                   (privilege.id_privileges && privilege.id_privileges.toLowerCase().includes(searchTerm));
        });

        renderPrivileges(filtered);
    }

    function renderPrivileges(privileges) {
        privilegesTableBody.innerHTML = '';

        if (!privileges || privileges.length === 0) {
            privilegesTableBody.innerHTML = `
                <tr>
                    <td colspan="2" style="text-align: center; padding: 20px; color: #7f8c8d;">
                        Ничего не найдено
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

    window.savePrivilegesData = function(data) {
        allPrivileges = data;
        renderPrivileges(data);
    };

    document.getElementById('closeViewAllPrivilegesForm')?.addEventListener('click', function() {
        allPrivileges = [];
        privilegesTableBody.innerHTML = '';
        if (privilegesSearch) privilegesSearch.value = '';
    });
});