<?php
    function getUserData() {
        // Проверяем, существует ли $_SESSION['userid']
        if (!isset($_SESSION['userid'])) {
            logger("ERROR", "User ID not found in session");
            return ['error' => 'User ID not found in session'];
        }

        try {
            // Логируем начало функции
            logger("DEBUG", "Начало выполнения функции getUserData()");

            // Открываем соединение с базой данных
            $pdo = connectToDatabase();
            logger("DEBUG", "Успешное подключение к базе данных");

            // Получаем userid из сессии
            $userid = $_SESSION['userid'];
            logger("DEBUG", "Получен User ID из сессии: " . $userid);

            // Подготавливаем SQL-запрос для выборки данных пользователя
            $query = "SELECT userlogin, family, name, full_name, email, roleid, active, add_ldap, tg_username, tg_id, telephone, dn 
                    FROM users 
                    WHERE userid = :userid";
            logger("DEBUG", "Подготовлен SQL-запрос для получения данных пользователя: " . $query);

            // Подготавливаем и выполняем запрос
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':userid', $userid, PDO::PARAM_INT);
            $stmt->execute();
            logger("DEBUG", "Выполнен запрос к таблице users для User ID: " . $userid);

            // Получаем результат в виде ассоциативного массива
            $userResult = $stmt->fetch(PDO::FETCH_ASSOC);

            // Если пользователь не найден, возвращаем сообщение об ошибке
            if (!$userResult) {
                logger("ERROR", "Пользователь с User ID " . $userid . " не найден");
                return ['error' => 'User not found'];
            } else {
                logger("DEBUG", "Получены данные пользователя: " . json_encode($userResult));
            }

            // Получаем names_rol из таблицы name_rol по roleid
            $roleid = $userResult['roleid'];
            logger("DEBUG", "Получен Role ID для запроса: " . $roleid);

            $roleQuery = "SELECT names_rol FROM name_rol WHERE roleid = :roleid";
            logger("DEBUG", "Подготовлен SQL-запрос для получения names_rol: " . $roleQuery);

            $roleStmt = $pdo->prepare($roleQuery);
            $roleStmt->bindParam(':roleid', $roleid, PDO::PARAM_INT);
            $roleStmt->execute();
            logger("DEBUG", "Выполнен запрос к таблице name_rol для Role ID: " . $roleid);

            // Получаем значение names_rol
            $roleResult = $roleStmt->fetch(PDO::FETCH_ASSOC);

            // Если роль не найдена, добавляем ошибку
            if (!$roleResult) {
                logger("WARN", "Роль с Role ID " . $roleid . " не найдена");
                $userResult['names_rol'] = 'Role not found';
            } else {
                logger("DEBUG", "Получено значение names_rol: " . $roleResult['names_rol']);
                $userResult['names_rol'] = $roleResult['names_rol'];
            }

            // Логируем успешное завершение функции
            logger("INFO", "Функция getUserData() успешно завершена");

            // Возвращаем полный массив данных
            return $userResult;

        } catch (PDOException $e) {
            // Логируем ошибку и возвращаем сообщение об ошибке
            logger("ERROR", "Произошла ошибка базы данных: " . $e->getMessage());
            error_log("Database error: " . $e->getMessage());
            return ['error' => 'Database error occurred'];
        }
    }
?>