<?php

    logger("INFO", "getVersionFromApi загружен");

    // Функция для получения версии через API
    function getVersionFromApi() {
        $apiUrl = 'http://localhost:5000/version/';
        $defaultVersion = '0.0.0';
        
        // Инициализация cURL
        $ch = curl_init();
        
        // Настройка параметров cURL
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3); // Таймаут 3 секунды
        
        // Выполнение запроса
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Проверка на ошибки
        if (curl_errno($ch)) {
            logger("ERROR", "cURL error: " . curl_error($ch));
            curl_close($ch);
            return $defaultVersion;
        }
        
        curl_close($ch);
        
        // Проверка HTTP-кода ответа
        if ($httpCode !== 200) {
            logger("ERROR", "API returned HTTP code: " . $httpCode);
            return $defaultVersion;
        }
        
        // Декодирование JSON
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            logger("ERROR", "Failed to decode API response: " . json_last_error_msg());
            return $defaultVersion;
        }
        
        // Получение версии из ответа
        return $data['version'] ?? $defaultVersion;
    }
?>