// Функция для формирования URL API с портом 5000
function getApiUrl(endpoint) {
    const protocol = window.location.protocol;
    let host = window.location.host.replace(/:80|:443/, '');
    return `${protocol}//${host}:5000${endpoint}`;
}

// Функция для выполнения проверки токена
async function verifyToken() {
    const accessToken = localStorage.getItem('access_token');
    
    // Если токена нет и мы не на странице логина - редирект
    if (!accessToken && !window.location.pathname.includes('/login')) {
        console.log('Access token not found, redirecting to logout');
        window.location.href = '/platform/logout.php';
        return;
    }

    try {
        // 1. Запрос на проверку токена
        const verifyResponse = await fetch(getApiUrl('/auth/verify'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${accessToken}`
            },
            body: JSON.stringify({
                token: accessToken
            })
        });

        // Обработка HTTP ошибок
        if (!verifyResponse.ok) {
            throw new Error(`HTTP error! status: ${verifyResponse.status}`);
        }

        const verifyData = await verifyResponse.json();

        if (verifyData.valid) {
            console.log('Token is valid');
            return;
        }

        if (verifyData.should_refresh) {
            console.log('Token expired, attempting to refresh');
            const refreshToken = localStorage.getItem('refresh_token');
            
            if (!refreshToken) {
                console.log('Refresh token not found, redirecting to logout');
                window.location.href = '/platform/logout.php';
                return;
            }

            // 2. Запрос на обновление токена
            const refreshResponse = await fetch(getApiUrl('/auth/refresh'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${accessToken}`
                },
                body: JSON.stringify({
                    refresh_token: refreshToken
                })
            });

            // Обработка HTTP ошибок при обновлении
            if (!refreshResponse.ok) {
                throw new Error(`Refresh failed! status: ${refreshResponse.status}`);
            }

            const refreshData = await refreshResponse.json();

            if (!refreshData.access_token || !refreshData.refresh_token) {
                console.log('Invalid refresh response, redirecting to logout');
                window.location.href = '/platform/logout.php';
                return;
            }

            // Сохраняем новые токены
            localStorage.setItem('access_token', refreshData.access_token);
            localStorage.setItem('refresh_token', refreshData.refresh_token);
            
            if (refreshData.user_id) {
                localStorage.setItem('user_id', refreshData.user_id);
            }
            
            if (refreshData.user_name) {
                localStorage.setItem('user_name', refreshData.user_name);
            }

            // 3. Сохраняем сессию на сервере (используем обычный URL без порта 5000)
            const saveResponse = await fetch(`${window.location.origin}/platform/back/save_session.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Auth-Token': 'SECRET_TOKEN_123'
                },
                body: JSON.stringify({
                    access_token: refreshData.access_token,
                    refresh_token: refreshData.refresh_token,
                    user_id: refreshData.user_id || localStorage.getItem('user_id'),
                    user_name: refreshData.user_name || localStorage.getItem('user_name')
                })
            });

            if (!saveResponse.ok) {
                console.error('Failed to save session');
            }

            console.log('Tokens refreshed successfully');
        } else {
            console.log('Token is invalid and cannot be refreshed, redirecting to logout');
            window.location.href = '/platform/logout.php';
        }
    } catch (error) {
        console.error('Error during token verification:', error);
        // Не делаем редирект при ошибке сети
        if (error.message.includes('NetworkError') || error.message.includes('Failed to fetch')) {
            console.log('Network error, skipping logout');
            return;
        }
        window.location.href = '/platform/logout.php';
    }
}

// Запускаем проверку с задержкой
function startTokenVerification() {
    // Первая проверка через 10 секунд после загрузки
    setTimeout(verifyToken, 10000);
    
    // Затем каждые 2 минуты
    setInterval(verifyToken, 2 * 60 * 1000);
}

// Запускаем только если мы не на странице логина
if (!window.location.pathname.includes('/login')) {
    document.addEventListener('DOMContentLoaded', startTokenVerification);
}