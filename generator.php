<?php
    // Функция для генерации GUID
    function generateGUID() {
        if (function_exists('com_create_guid') === true) {
            // Используем встроенную функцию, если она доступна (обычно на Windows)
            return trim(com_create_guid(), '{}');
        }
        // Ручная генерация GUID, если com_create_guid недоступен
        $data = random_bytes(16); // Генерируем 16 байт случайных данных
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Устанавливаем версию (4)
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Устанавливаем биты (10)
       // Форматируем GUID в стандартный вид
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }   
    // Генерация GUID
    $guid = generateGUID();
    // Устанавливаем заголовок для возврата JSON
    header('Content-Type: application/json');
    // Возвращаем GUID в формате JSON
    echo json_encode([
        'guid' => $guid,
        'status' => 'success'
    ]);
?>