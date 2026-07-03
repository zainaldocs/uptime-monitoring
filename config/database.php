<?php
/**
 * Database Configuration and PDO Connection Provider
 */

// Native simple .env parser
function loadEnv(string $filePath): void {
    if (!file_exists($filePath)) {
        return;
    }
    
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // Ignore comments
        if (strpos($line, '#') === 0 || empty($line)) {
            continue;
        }
        
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $val = trim($parts[1]);
            
            // Strip surrounding quotes
            if (preg_match('/^([\'"])(.*)\1$/', $val, $matches)) {
                $val = $matches[2];
            }
            
            putenv("{$key}={$val}");
            $_ENV[$key] = $val;
            $_SERVER[$key] = $val;
        }
    }
}

// Load env configurations
loadEnv(__DIR__ . '/../.env');

// DB Constants with env support & fallback values
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'db_uptime_monitoring');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost/uptime-monitoring/');

/**
 * Returns a PDO database connection instance.
 * Uses a static variable to implement the Singleton pattern for the request lifecycle.
 * 
 * @return PDO
 * @throws PDOException
 */
function getDBConnection(): PDO {
    static $pdo = null;
    
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log error internally and do not expose connection details in production/live mode
            error_log("Database connection failed: " . $e->getMessage());
            throw new PDOException("Database connection failed. Please check server configurations.", (int)$e->getCode());
        }
    }
    
    return $pdo;
}

