<?php
// ── db_config.php — shared DB connection ─────────────────
// require_once 'db_config.php'; in every file that needs DB

define('DB_HOST', 'localhost');
define('DB_USER', 'u627631172_boycold');
define('DB_PASS', 'Boycold21');
define('DB_NAME', 'u627631172_boycold_db');

$connect = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($connect->connect_error) {
    die('Database connection failed: ' . $connect->connect_error);
}
$connect->set_charset('utf8mb4');