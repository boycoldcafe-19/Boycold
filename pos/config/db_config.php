<?php
// Keep POS and the rest of the application on one database connection.
// The former duplicate config looked for `pos/.env`, which does not exist.
require_once dirname(__DIR__, 2) . '/config/db_config.php';
