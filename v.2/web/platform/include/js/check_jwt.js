// Функция для выполнения проверки токена
async function verifyToken() {
    const baseUrl = window.location.origin;
    const accessToken = localStorage.getItem('access_token');
    
    if (!accessToken) {
        console.log('Access token not found, redirecting to logout');
        window.location.href = '/platform/logout.php';
        return;
    }

    try {
        // Запрос на проверку токена
        const verifyResponse = await fetch(`${baseUrl}/auth/verify`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                token: accessToken
            })
        });

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

            // Запрос на обновление токена
            const refreshResponse = await fetch(`${baseUrl}/auth/refresh`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    refresh_token: refreshToken
                })
            });

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

            // Сохраняем сессию на сервере
            await fetch(`${baseUrl}/platform/back/save_session.php`, {
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

            console.log('Tokens refreshed successfully');
        } else {
            console.log('Token is invalid and cannot be refreshed, redirecting to logout');
            window.location.href = '/platform/logout.php';
        }
    } catch (error) {
        console.error('Error during token verification:', error);
        window.location.href = '/platform/logout.php';
    }
}

// Запускаем проверку каждые 2 минуты
function startTokenVerification() {
    // Первая проверка сразу при запуске
    verifyToken();
    
    // Затем каждые 2 минуты
    setInterval(verifyToken, 2 * 60 * 1000);
}

// Запускаем процесс проверки
startTokenVerification();