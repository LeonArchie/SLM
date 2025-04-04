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
;
        addLogEntry('Инициализация проверки...', 'log');

        eventSource = new EventSource(`back/Server_global_Check/server_global_check_start.php?userid=${userId}&csrf_token=${csrfToken}`);

        eventSource.onmessage = function(e) {
            try {
                const data = JSON.parse(e.data);

                // Проверяем структуру данных
                if (!data || typeof data !== 'object' || !data.message || !data.type) {
                    throw new Error('Некорректные данные от сервера');
                }

                // Добавляем запись в лог
                addLogEntry(data.message, data.type);

                // Завершаем проверку, если это последнее сообщение
                if (data.type === 'done') {
                    stopBtn.disabled = true;
                    startBtn.disabled = false;
                    eventSource.close();
                }
            } catch (error) {
                console.error('Ошибка обработки данных:', error);
                addLogEntry('Ошибка обработки данных сервера', 'error');
            }
        };

        eventSource.onerror = function() {
            console.error('Произошла ошибка EventSource.');
            addLogEntry('Соединение с сервером прервано', 'error');
            stopBtn.disabled = true;
            startBtn.disabled = false;
            if (eventSource) eventSource.close();
        };
    });

    // Остановка проверки
    stopBtn.addEventListener('click', function() {
        if (eventSource) {
            eventSource.close();
        }

        fetch('back/Server_global_Check/server_global_check_stop.php', {
            headers: {
                'Accept': 'application/json; charset=utf-8'
            }
        })
        .then(response => response.json())
        .then(data => {
            const message = data.message || 'Проверка остановлена';
            addLogEntry(message, data.status === 'success' ? 'warning' : 'error');
            stopBtn.disabled = true;
            startBtn.disabled = false;
        })
        .catch(error => {
            console.error('Ошибка сети при попытке остановки:', error);
            addLogEntry('Ошибка сети при попытке остановки', 'error');
            stopBtn.disabled = true;
            startBtn.disabled = false;
        });
    });
});