async function checkPrivilege(privilegesPage) {
    try {
        // Проверяем наличие необходимых данных в сессии
        if (!sessionStorage.getItem('access_token') || !sessionStorage.getItem('userid')) {
            console.error('Ошибка: Отсутствует access_token или userid в сессии');
            return false;
        }
        
        // Автоматически определяем базовый URL с портом 5000
        const protocol = window.location.protocol;
        let host = window.location.host.replace(/:80|:443/, '');
        const apiUrl = `${protocol}//${host}:5000/privileges/check-privilege`;
        
        // Подготавливаем данные для запроса
        const postData = {
            access_token: sessionStorage.getItem('access_token'),
            privileges_id: privilegesPage,
            userid: sessionStorage.getItem('userid')
        };
        
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(postData),
            signal: AbortSignal.timeout(5000) // Таймаут 5 секунд
        });
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error(`Ошибка API: HTTP ${response.status} - ${errorText}`);
            return false;
        }
        
        const responseData = await response.json();
        return responseData.has_privilege || false;
        
    } catch (error) {
        console.error(`Ошибка при проверке привилегий: ${error.name} - ${error.message}`);
        return false;
    }
}