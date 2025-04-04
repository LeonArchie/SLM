document.addEventListener('DOMContentLoaded', function() {
    const startBtn = document.getElementById('StartScript');
    const stopBtn = document.getElementById('StopScript');
    const tableContainer = document.querySelector('.table-container');
    
    // Инициализация контейнера
    tableContainer.innerHTML = '<div class="log-header">Журнал проверки серверов:</div>';
    
    let eventSource;

    // Функция для добавления записи в лог
    function addLogEntry(message, type = 'log') {
        const logEntry = document.createElement('div');
        logEntry.className = `log-entry ${type}`;
        
        const timeSpan = document.createElement('span');
        timeSpan.className = 'log-time';
        timeSpan.textContent = `[${new Date().toLocaleTimeString()}]`;
        
        const messageSpan = document.createElement('span');
        messageSpan.className = 'log-message';
        messageSpan.textContent = message;
        
        logEntry.appendChild(timeSpan);
        logEntry.appendChild(messageSpan);
        tableContainer.appendChild(logEntry);
        tableContainer.scrollTop = tableContainer.scrollHeight;
    }

    // Запуск проверки
    startBtn.addEventListener('click', function() {
        tableContainer.innerHTML = '<div class="log-header">Журнал проверки серверов:</div>';
        stopBtn.disabled = false;
        startBtn.disabled = true;
        
        const csrfToken = document.querySelector('input[name="csrf_token"]').value;
        const userId = document.querySelector('input[name="userid"]').value;
        
        addLogEntry('Инициализация проверки...', 'log');
        
        eventSource = new EventSource(`back/Server_global_Check/server_global_check_start.php?userid=${userId}&csrf_token=${csrfToken}`);
        
        eventSource.onmessage = function(e) {
            try {
                const data = JSON.parse(e.data);
                addLogEntry(data.message, data.type);
                
                if (data.type === 'error' || data.type === 'success') {
                    stopBtn.disabled = true;
                    startBtn.disabled = false;
                    eventSource.close();
                }
            } catch (error) {
                addLogEntry('Ошибка обработки сообщения от сервера', 'error');
                console.error('Ошибка парсинга:', e.data, error);
            }
        };
        
        eventSource.onerror = function() {
            addLogEntry('Соединение с сервером прервано', 'error');
            stopBtn.disabled = true;
            startBtn.disabled = false;
            if (eventSource) eventSource.close();
        };
    });

    // Остановка проверки
    stopBtn.addEventListener('click', function() {
        if (eventSource) eventSource.close();
        
        fetch('back/Server_global_Check/server_global_check_stop.php', {
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                addLogEntry(data.message || 'Проверка остановлена пользователем', 'warning');
            } else {
                addLogEntry(data.message || 'Не удалось отменить проверку', 'error');
            }
            stopBtn.disabled = true;
            startBtn.disabled = false;
        })
        .catch(error => {
            addLogEntry('Ошибка сети при попытке остановки', 'error');
            stopBtn.disabled = true;
            startBtn.disabled = false;
        });
    });
});