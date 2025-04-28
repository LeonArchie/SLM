// Функция для формирования URL API с портом 5000
function getApiUrl(endpoint) {
    const protocol = window.location.protocol;
    let host = window.location.host.replace(/:80|:443/, '');
    return `${protocol}//${host}:5000${endpoint}`;
}

// Функция для выполнения проверки токена
async function verifyToken() {
    const accessToken = localStorage.getItem('access_token');
    
    if (!accessToken) {
        if (!window.location.pathname.includes('/login')) {
            window.location.href = '/platform/logout.php';
        }
        return;
    }

    try {
        // 1. Проверка токена
        const verifyResponse = await fetch(getApiUrl('/auth/verify'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${accessToken}`
            },
            body: JSON.stringify({ token: accessToken })
        });

        if (!verifyResponse.ok) throw new Error(`HTTP error! status: ${verifyResponse.status}`);

        const verifyData = await verifyResponse.json();

        if (verifyData.valid) return;

        if (verifyData.should_refresh) {
            const refreshToken = localStorage.getItem('refresh_token');
            if (!refreshToken) {
                window.location.href = '/platform/logout.php';
                return;
            }

            // 2. Обновление токена (убрал Authorization header для этого запроса)
            const refreshResponse = await fetch(getApiUrl('/auth/refresh'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ refresh_token: refreshToken })
            });

            if (!refreshResponse.ok) {
                console.error('Refresh token failed:', refreshResponse.status);
                window.location.href = '/platform/logout.php';
                return;
            }

            const refreshData = await refreshResponse.json();
            
            // 3. Проверка ответа
            if (!refreshData.access_token || !refreshData.refresh_token) {
                console.error('Invalid tokens in refresh response');
                window.location.href = '/platform/logout.php';
                return;
            }

            // 4. Сохранение новых токенов
            localStorage.setItem('access_token', refreshData.access_token);
            localStorage.setItem('refresh_token', refreshData.refresh_token);
            
            // 5. Обновление сессии на сервере
            try {
                await fetch(`${window.location.origin}/platform/back/save_session.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        access_token: refreshData.access_token,
                        refresh_token: refreshData.refresh_token,
                        user_id: refreshData.user_id || localStorage.getItem('user_id'),
                        user_name: refreshData.user_name || localStorage.getItem('user_name')
                    })
                });
            } catch (e) {
                console.error('Session save error:', e);
            }
        } else {
            window.location.href = '/platform/logout.php';
        }
    } catch (error) {
        console.error('Token verification error:', error);
        if (!error.message.includes('NetworkError')) {
            window.location.href = '/platform/logout.php';
        }
    }
}

// Запуск проверки
function initTokenVerification() {
    // Первая проверка через 5 сек после загрузки
    setTimeout(verifyToken, 5000);
    // Последующие проверки каждые 90 секунд
    setInterval(verifyToken, 90000);
}

if (!window.location.pathname.includes('/login')) {
    document.addEventListener('DOMContentLoaded', initTokenVerification);
}