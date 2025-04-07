<?php
    function checkAuth() {
        // Параметры API
        $apiBaseUrl = 'http://your-api-domain';
        $verifyEndpoint = '/auth/verify';
        $refreshEndpoint = '/auth/refresh';
        
        logger("INFO", "Начало проверки авторизации через сессию");
        
        // Проверяем наличие токенов в сессии
        $accessToken = $_SESSION['access_token'] ?? null;
        $refreshToken = $_SESSION['refresh_token'] ?? null;
        
        if (!$accessToken) {
            logger("WARNING", "Access токен отсутствует в сессии");
            return false;
        }

        logger("DEBUG", "Найден access токен в сессии");
        
        // Функция для выполнения запросов к API
        $makeRequest = function($url, $data) use ($apiBaseUrl) {
            logger("DEBUG", "Выполнение запроса к API: ".$url);
            
            $ch = curl_init($apiBaseUrl . $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            logger("DEBUG", "Ответ API (код {$httpCode}): ".substr($response, 0, 200));
            
            return [
                'status' => $httpCode,
                'response' => json_decode($response, true)
            ];
        };

        // 1. Проверяем текущий access токен
        logger("INFO", "Проверка валидности access токена");
        $verifyResult = $makeRequest($verifyEndpoint, ['token' => $accessToken]);
        
        // Если токен валиден
        if ($verifyResult['status'] == 200 && $verifyResult['response']['valid'] === true) {
            logger("INFO", "Access токен валиден");
            return true;
        }
        
        // Если токен можно обновить
        if ($verifyResult['status'] == 401 && 
            ($verifyResult['response']['should_refresh'] ?? false) && 
            $refreshToken) {
            
            logger("INFO", "Попытка обновления токенов");
            
            // 2. Пытаемся обновить токены
            $refreshResult = $makeRequest($refreshEndpoint, ['refresh_token' => $refreshToken]);
            
            if ($refreshResult['status'] == 200) {
                logger("INFO", "Токены успешно обновлены");
                
                // Сохраняем новые токены в сессию
                $_SESSION['access_token'] = $refreshResult['response']['access_token'];
                $_SESSION['refresh_token'] = $refreshResult['response']['refresh_token'];
                
                return true;
            } else {
                logger("WARNING", "Не удалось обновить токены (код {$refreshResult['status']})");
            }
        }
        
        logger("WARNING", "Авторизация не пройдена, очистка сессии");
        
        // Если авторизация не прошла - чистим сессию
        unset($_SESSION['access_token']);
        unset($_SESSION['refresh_token']);
        session_destroy();
        
        return false;
    }

    logger("INFO", "Скрипт авторизации инициализирован (режим сессии)");

    // Основная логика
    if (!checkAuth()) {
        logger("WARNING", "Авторизация не пройдена, перенаправление на login");
        header("Location: platform/login.php");
    }
    exit();
?>