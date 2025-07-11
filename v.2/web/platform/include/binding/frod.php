<?php
    // SPDX-License-Identifier: AGPL-3.0-only WITH LICENSE-ADDITIONAL
    // Copyright (C) 2025 Петунин Лев Михайлович
   
    if (!checkPrivilege($privileges_page)) {
        logger("ERROR", "Недостаточно привилегий");
        header("Location: /err/403.html");
    }
?>  