document.addEventListener('DOMContentLoaded', function() {
    const startBtn = document.getElementById('StartScript');
    const stopBtn = document.getElementById('StopScript');
    const tableContainer = document.querySelector('.table-container');

    // Инициализация контейнера
    tableContainer.innerHTML = '<div class="log-header">Журнал проверки серверов:</div>';

    let eventSource;

    console.log('Скрипт загружен и готов к работе.');

    // Функция для добавления записи в лог
    function addLogEntry(message, type = 'log') {
        console.log(`Добавление записи в лог: Тип=${type}, Сообщение=${message}`);
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
        console.log('Нажата кнопка "Запустить проверку".');
        tableContainer.innerHTML = '<div class="log-header">Журнал проверки серверов:</div>';
        stopBtn.disabled = false;
        startBtn.disabled = true;

        const csrfToken = document.querySelector('input[name="csrf_token"]').value;
        const userId = document.querySelector('input[name="userid"]').value;

        console.log(`Получены данные для запроса: CSRF Token=${csrfToken}, UserID=${userId}`);
        addLogEntry('Инициализация проверки...', 'log');

        eventSource = new EventSource(`back/Server_global_Check/server_global_check_start.php?userid=${userId}&csrf_token=${csrfToken}`);

        console.log('EventSource создан. Ожидание сообщений от сервера...');

        eventSource.onmessage = function(e) {
            console.log('Получено новое сообщение от сервера:', e.data);
            try {
                const data = JSON.parse(e.data);
                console.log('Данные успешно распарсены:', data);

                // Проверяем структуру данных
                if (!data || typeof data !== 'object' || !data.message || !data.type) {
                    throw new Error('Некорректные данные от сервера');
                }

                // Добавляем запись в лог
                addLogEntry(data.message, data.type);

                // Завершаем проверку, если это последнее сообщение
                if (data.type === 'done') {
                    console.log('Получено завершающее сообщение. Закрытие EventSource.');
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
        console.log('Нажата кнопка "Остановить проверку".');
        if (eventSource) {
            console.log('Закрытие EventSource.');
            eventSource.close();
        }

        fetch('back/Server_global_Check/server_global_check_stop.php', {
            headers: {
                'Accept': 'application/json; charset=utf-8'
            }
        })
        .then(response => response.json())
        .then(data => {
            console.log('Данные от сервера после остановки:', data);
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