<?php
    /**
     * Назначает привилегии пользователям.
     *
     * @param PDO $pdo Объект PDO для работы с базой данных.
     * @param array $userIDs Массив идентификаторов пользователей.
     * @param array $privileges Массив идентификаторов привилегий.
     * @throws Exception В случае ошибки при выполнении транзакции.
     */
    function assignPrivileges($pdo, $userIDs, $privileges) {
        try {
            // Логируем начало транзакции
            logger("INFO", "Начало транзакции для назначения привилегий.");

            // Начинаем транзакцию
            $pdo->beginTransaction();

            // Логируем количество пользователей и привилегий
            logger("INFO", "Обработка " . count($userIDs) . " пользователей и " . count($privileges) . " привилегий.");

            foreach ($userIDs as $userID) {
                // Логируем начало обработки пользователя
                logger("INFO", "Обработка пользователя с ID: $userID.");

                foreach ($privileges as $privilegeID) {
                    // Логируем проверку привилегии
                    logger("DEBUG", "Проверка привилегии $privilegeID для пользователя $userID.");

                    // Проверяем, не назначена ли уже привилегия пользователю
                    $stmt = $pdo->prepare("
                        SELECT id FROM privileges 
                        WHERE userid = :userid AND id_privileges = :privilegeID
                    ");
                    $stmt->execute(['userid' => $userID, 'privilegeID' => $privilegeID]);
                    $existingRecord = $stmt->fetch();

                    // Если привилегия уже назначена, пропускаем
                    if ($existingRecord) {
                        logger("INFO", "Привилегия $privilegeID уже назначена пользователю $userID. Пропуск.");
                        audit("INFO", "Привилегия $privilegeID уже назначена пользователю $userID. Пропуск.");
                        continue;
                    }

                    // Генерируем новый GUID для записи
                    $newID = generateGUID();

                    // Логируем добавление привилегии
                    logger("INFO", "Добавление привилегии $privilegeID пользователю $userID.");

                    // Добавляем привилегию пользователю
                    $stmt = $pdo->prepare("
                        INSERT INTO privileges (id, userid, id_privileges) 
                        VALUES (:id, :userid, :privilegeID)
                    ");
                    $stmt->execute([
                        'id' => $newID,
                        'userid' => $userID,
                        'privilegeID' => $privilegeID
                    ]);

                    logger("INFO", "Привилегия $privilegeID успешно назначена пользователю $userID.");
                    audit("INFO", "Привилегия $privilegeID успешно назначена пользователю $userID.");
                }

                // Логируем завершение обработки пользователя
                logger("INFO", "Завершена обработка пользователя с ID: $userID.");
            }

            // Логируем успешное завершение транзакции
            logger("INFO", "Транзакция успешно завершена.");

            // Завершаем транзакцию
            $pdo->commit();
        } catch (Exception $e) {
            // Логируем ошибку
            logger("ERROR", "Ошибка в функции assignPrivileges: " . $e->getMessage());

            // Откатываем транзакцию в случае ошибки
            if ($pdo->inTransaction()) {
                logger("INFO", "Откат транзакции из-за ошибки.");
                $pdo->rollBack();
            }

            // Пробрасываем исключение дальше
            throw $e;
        }
    }


    /**
 * Функция для снятия привилегий у пользователей.
 *
 * @param PDO $pdo Объект PDO для работы с базой данных.
 * @param array $userIDs Массив идентификаторов пользователей.
 * @param array $privileges Массив идентификаторов привилегий.
 * @return PDOException|null Возвращает исключение в случае ошибки, иначе null.
 */
    function revokePrivileges(PDO $pdo, array $userIDs, array $privileges): ?PDOException
    {
        logger("INFO", "Начало обработки запроса на снятие привилегий.");

        try {
            // Начинаем транзакцию
            logger("INFO", "Начало транзакции...");
            $pdo->beginTransaction();
            logger("INFO", "Транзакция начата.");

            // Перебираем всех пользователей
            logger("INFO", "Перебор пользователей...");
            foreach ($userIDs as $userID) {
                logger("INFO", "Обработка пользователя: $userID");

                // Перебираем все привилегии
                logger("INFO", "Перебор привилегий для пользователя $userID...");
                foreach ($privileges as $privilegeID) {
                    logger("INFO", "Обработка привилегии: $privilegeID для пользователя $userID");

                    // Удаляем привилегию у пользователя
                    logger("INFO", "Удаление привилегии $privilegeID у пользователя $userID...");
                    $stmt = $pdo->prepare("
                        DELETE FROM privileges 
                        WHERE userid = :userid AND id_privileges = :privilegeID
                    ");
                    $stmt->execute([
                        'userid' => $userID,
                        'privilegeID' => $privilegeID
                    ]);

                    // Логируем результат
                    if ($stmt->rowCount() > 0) {
                        logger("INFO", "Привилегия $privilegeID успешно снята у пользователя $userID.");
                    } else {
                        logger("INFO", "Привилегия $privilegeID не была назначена пользователю $userID.");
                    }
                }
            }

            // Завершаем транзакцию
            logger("INFO", "Завершение транзакции...");
            $pdo->commit();
            logger("INFO", "Транзакция завершена успешно.");

            // Возвращаем null, если ошибок не было
            return null;
        } catch (PDOException $e) {
            // Откатываем транзакцию в случае ошибки
            logger("ERROR", "Ошибка при выполнении транзакции: " . $e->getMessage());
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
                logger("INFO", "Транзакция откачена.");
            }

            // Возвращаем исключение
            return $e;
        }

        logger("INFO", "Обработка запроса на снятие привилегий завершена.");
    }
?>