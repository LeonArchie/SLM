<?php
    // Функция для получения данных пользователя по его ID
    function getUserData($userid) {
        try {
            // Подключение к базе данных
            $pdo = connectToDatabase();

            // SQL-запрос для выборки данных пользователя
            $query = "SELECT userlogin, family, name, full_name, email, active, add_ldap, tg_username, tg_id, telephone, dn 
                    FROM users 
                    WHERE userid = :userid";

            // Подготовка SQL-запроса
            $stmt = $pdo->prepare($query);

            // Привязка параметра :userid к переменной $userid с указанием типа данных (целое число)
            $stmt->bindParam(':userid', $userid, PDO::PARAM_INT);

            // Выполнение подготовленного запроса
            $stmt->execute();

            // Получение результата запроса в виде ассоциативного массива
            $userResult = $stmt->fetch(PDO::FETCH_ASSOC);

            // Проверка, найдены ли данные пользователя
            if (!$userResult) {
                // Логирование ошибки, если пользователь не найден
                logger("ERROR", "Пользователь с User ID " . $userid . " не найден");
                // Возврат массива с сообщением об ошибке
                return ['error' => 'Пользователь не найдет'];
            }

            // Возврат массива с данными пользователя, если они найдены
            return $userResult;

        } catch (PDOException $e) {
            // Логирование ошибки, если произошла ошибка базы данных
            logger("ERROR", "Произошла ошибка базы данных: " . $e->getMessage());
            // Возврат массива с сообщением об ошибке
            return ['error' => 'Ошибка базы данных'];
        }
    }
?>