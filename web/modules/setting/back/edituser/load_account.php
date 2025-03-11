<?php
    function getUserData($userid) {
        try {
            $pdo = connectToDatabase();

            $query = "SELECT userlogin, family, name, full_name, email, active, add_ldap, tg_username, tg_id, telephone, dn 
                    FROM users 
                    WHERE userid = :userid";

            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':userid', $userid, PDO::PARAM_INT);
            $stmt->execute();

            $userResult = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$userResult) {
                logger("ERROR", "Пользователь с User ID " . $userid . " не найден");
                return ['error' => 'User not found'];
            }

            // Возвращаем массив данных
            return $userResult;

        } catch (PDOException $e) {
            logger("ERROR", "Произошла ошибка базы данных: " . $e->getMessage());
            error_log("Database error: " . $e->getMessage());
            return ['error' => 'Database error occurred'];
        }
    }
?>