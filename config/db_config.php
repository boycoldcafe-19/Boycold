<?php
// ── db_config.php — shared DB connection ─────────────────
// Load .env file if it exists (for production)
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Skip comments
        if (strpos($line, '=') === false) continue;
        
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        
        // Remove quotes if present
        if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
            (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
            $value = substr($value, 1, -1);
        }
        
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

// Define database configuration with environment variable fallback
define('DB_HOST', getenv('DB_HOST') ?: $_ENV['DB_HOST'] ?? 'localhost');
define('DB_USER', getenv('DB_USER') ?: $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', getenv('DB_PASS') ?: $_ENV['DB_PASS'] ?? '');
define('DB_NAME', getenv('DB_NAME') ?: $_ENV['DB_NAME'] ?? 'boycold_db');
define('DB_PORT', getenv('DB_PORT') ?: $_ENV['DB_PORT'] ?? 3306);

// Create database connection with error handling
try {
    $connect = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int)DB_PORT);
    
    if ($connect->connect_error) {
        // Log error to file instead of displaying
        $errorMsg = 'Database connection failed: ' . $connect->connect_error;
        error_log($errorMsg);
        
        // Check if we're in debug mode
        if (getenv('APP_DEBUG') || $_ENV['APP_DEBUG'] ?? false) {
            die($errorMsg);
        } else {
            // Show generic error message in production
            header('HTTP/1.1 500 Internal Server Error');
            die('A database connection error occurred. Please contact support.');
        }
    }
    
    $connect->set_charset('utf8mb4');
    
    // Test connection using query instead of deprecated ping()
    $testQuery = $connect->query("SELECT 1");
    if (!$testQuery) {
        $errorMsg = 'Database connection lost after initialization';
        error_log($errorMsg);
        
        if (getenv('APP_DEBUG') || $_ENV['APP_DEBUG'] ?? false) {
            die($errorMsg);
        } else {
            header('HTTP/1.1 500 Internal Server Error');
            die('A database connection error occurred. Please contact support.');
        }
    }
} catch (Exception $e) {
    $errorMsg = 'Database exception: ' . $e->getMessage();
    error_log($errorMsg);
    
    if (getenv('APP_DEBUG') || $_ENV['APP_DEBUG'] ?? false) {
        die($errorMsg);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        die('A database connection error occurred. Please contact support.');
    }
}