<?php
    function checkAuth() {
        // Параметры API
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $host = str_replace([':80',':443'], '', $host);
        $apiBaseUrl = "{$protocol}://{$host}:5000";
        
        $verifyEndpoint = '/auth/verify';
        $refreshEndpoint = '/auth/refresh';
        $userActiveEndpoint = '/setting/user/active/';
        
        logger("INFO", "Начало проверки авторизации через сессию");
        
        // Проверяем наличие токенов в сессии
        $accessToken = $_SESSION['access_token'] ?? null;
        $refreshToken = $_SESSION['refresh_token'] ?? null;
        $userId = $_SESSION['userid'] ?? null;
        
        if (!$accessToken || !$userId) {
            logger("WARNING", "Access токен или user_id отсутствует в сессии");
            return false;
        }

        logger("DEBUG", "Найден access токен и user_id в сессии");
        
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
        
        $isTokenValid = false;
        
        // Если токен валиден
        if ($verifyResult['status'] == 200 && $verifyResult['response']['valid'] === true) {
            logger("INFO", "Access токен валиден");
            $isTokenValid = true;
        }
        // Если токен можно обновить
        elseif ($verifyResult['status'] == 401 && 
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
                $isTokenValid = true;
            } else {
                logger("WARNING", "Не удалось обновить токены (код {$refreshResult['status']})");
            }
        }
        
        // Если токен не валиден и не обновлен
        if (!$isTokenValid) {
            logger("WARNING", "Авторизация не пройдена, очистка сессии");
            unset($_SESSION['access_token']);
            unset($_SESSION['refresh_token']);
            session_destroy();
            return false;
        }
        
        // 3. Проверяем активный статус пользователя
        logger("INFO", "Проверка активного статуса пользователя");
        $activeStatusResult = $makeRequest($userActiveEndpoint, ['user_id' => $userId]);
        
        if ($activeStatusResult['status'] != 200 || !($activeStatusResult['response']['success'] ?? false)) {
            logger("WARNING", "Пользователь не активен или ошибка проверки статуса");
            unset($_SESSION['access_token']);
            unset($_SESSION['refresh_token']);
            session_destroy();
            return false;
        }
        
        logger("INFO", "Авторизация и проверка статуса успешно пройдены");
        return true;
    }

    logger("INFO", "Скрипт авторизации инициализирован (режим сессии)");

    // Основная логика
    if (!checkAuth()) {
        logger("WARNING", "Авторизация не пройдена, перенаправление на logout");
        header("Location: /platform/logout.php");
        exit();
    }
?>