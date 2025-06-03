<?php
    logger("DEBUG", "getLdapStatus подключен");
    // Функция для получения статуса LDAP через API
    function getLdapStatus() {
        $apiUrl = 'http://localhost:5000/ldap/active/';
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return $data['active'] ?? false;
        }
        
        // Логирование ошибки при недоступности API
        logger("ERROR", "Не удалось получить статус LDAP. HTTP код: $httpCode");
        return false;
    }
?>