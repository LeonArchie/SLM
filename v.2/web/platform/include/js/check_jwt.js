// Функция для формирования URL API
function getApiUrl(endpoint) {
    const protocol = window.location.protocol;
    const host = window.location.hostname;
    return `${protocol}//${host}:5000${endpoint}`;
}

// Основная функция проверки и обновления токена
async function verifyAndRefreshToken() {
    const accessToken = localStorage.getItem('access_token');
    const refreshToken = localStorage.getItem('refresh_token');

    // Если нет токенов и мы не на странице логина - выход
    if ((!accessToken || !refreshToken) && !window.location.pathname.includes('/login')) {
        window.location.href = '/platform/logout.php';
        return false;
    }

    try {
        // 1. Проверка текущего токена
        const verifyResponse = await fetch(getApiUrl('/auth/verify'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${accessToken}`
            },
            body: JSON.stringify({ token: accessToken })
        });

        // Если токен валиден - выходим
        if (verifyResponse.ok) {
            const data = await verifyResponse.json();
            if (data.valid) return true;
        }

        // 2. Если токен невалиден, пробуем обновить
        const refreshResponse = await fetch(getApiUrl('/auth/refresh'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ refresh_token: refreshToken })
        });

        if (!refreshResponse.ok) {
            console.error('Refresh failed:', refreshResponse.status);
            throw new Error('Refresh token failed');
        }

        const refreshData = await refreshResponse.json();

        // 3. Проверяем полученные новые токены
        if (!refreshData.access_token || !refreshData.refresh_token) {
            throw new Error('Invalid tokens received');
        }

        // 4. Сохраняем новые токены
        localStorage.setItem('access_token', refreshData.access_token);
        localStorage.setItem('refresh_token', refreshData.refresh_token);

        // 5. Обновляем сессию на сервере
        await updateServerSession(refreshData);

        return true;

    } catch (error) {
        console.error('Token verification failed:', error);
        if (!window.location.pathname.includes('/login')) {
            window.location.href = '/platform/logout.php';
        }
        return false;
    }
}

// Функция обновления сессии на сервере
async function updateServerSession(tokenData) {
    try {
        await fetch(`${window.location.origin}/platform/back/save_session.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                access_token: tokenData.access_token,
                refresh_token: tokenData.refresh_token,
                user_id: tokenData.user_id || localStorage.getItem('user_id'),
                user_name: tokenData.user_name || localStorage.getItem('user_name')
            })
        });
    } catch (e) {
        console.error('Failed to update server session:', e);
    }
}

// Запуск периодической проверки
function startTokenVerification() {
    // Первая проверка через 5 секунд
    setTimeout(async () => {
        await verifyAndRefreshToken();
    }, 5000);

    // Последующие проверки каждые 90 секунд
    setInterval(async () => {
        await verifyAndRefreshToken();
    }, 90000);
}

// Инициализация только на защищенных страницах
if (!window.location.pathname.includes('/login')) {
    document.addEventListener('DOMContentLoaded', startTokenVerification);
}