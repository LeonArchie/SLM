<?php
    if (!checkPrivilege($privileges_page)) {
        logger("ERROR", "Недостаточно привилегий");
        header("Location: /err/403.html");
    }
?>  