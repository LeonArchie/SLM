<?php

    // Проверяем, определена ли константа ROOT_PATH. Если нет, определяем её как корневой путь документа.
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
    }
        
    // Формируем путь к файлу function.php, который находится в папке include.
    $file_path = ROOT_PATH . '/include/function.php';

    // Проверяем, существует ли файл function.php. Если нет, выводим сообщение об ошибке и завершаем выполнение скрипта.
    if (!file_exists($file_path)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка 0038: Ошибка сервера.']);
        exit();
    }

    // Подключаем файл function.php.
    require_once $file_path;
    
    startSessionIfNotStarted();

    // Проверяем, что запрос пришел с нужной кнопки
    $input = json_decode(file_get_contents('php://input'), true);
    if (($input['source'] ?? '') !== 'toggle_button') {
        http_response_code(403);
        logger("ERROR", "Попытка доступа из неавторизованного источника");
        die(json_encode(['success' => false, 'message' => 'Доступ запрещен']));
    }

    // Проверяем авторизацию
    checkAuth();

    // Валидация данных
    $guid = $input['guid'] ?? null;
    $active = $input['active'] ?? null;

    if (!$guid || !isset($active)) {
        http_response_code(400);
        logger("ERROR", "Неверные параметры запроса: guid=" . ($guid ?? 'null') . ", active=" . ($active ?? 'null'));
        die(json_encode(['success' => false, 'message' => 'Неверные параметры запроса']));
    }

    // Обновляем статус модуля
    $filePath = ROOT_PATH . '/config/modules.json';
    $modulesData = json_decode(file_get_contents($filePath), true);

    function updateModuleStatus(&$modules, $guid, $active) {
        foreach ($modules as &$module) {
            if ($module['guid'] === $guid) {
                $module['active'] = (bool)$active;
                logger("DEBUG", "Найден модуль для обновления: " . $guid . ", новый статус: " . ($active ? 'активен' : 'неактивен'));
                return true;
            }
            if (!empty($module['dropdown'])) {
                if (updateModuleStatus($module['dropdown'], $guid, $active)) {
                    return true;
                }
            }
        }
        return false;
    }

    if (!updateModuleStatus($modulesData['menu'], $guid, $active)) {
        http_response_code(404);
        logger("ERROR", "Модуль не найден: " . $guid);
        die(json_encode(['success' => false, 'message' => 'Модуль не найден']));
    }

    // Сохраняем изменения
    if (file_put_contents($filePath, json_encode($modulesData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        logger("INFO", "Транзакция успешно завершена. Модуль " . $guid . " переведен в состояние: " . ($active ? 'активен' : 'неактивен'));
        echo json_encode(['success' => true, 'message' => 'Статус изменен']);
    } else {
        http_response_code(500);
        logger("ERROR", "Ошибка записи в файл модулей при изменении статуса модуля: " . $guid);
        echo json_encode(['success' => false, 'message' => 'Ошибка записи в файл']);
    }
?>