<?php
/**
 * Database configuration
 */
define('DB_HOST', 'localhost:3308');
define('DB_NAME', 'QUIZ');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Get a database connection
 * @return PDO The database connection
 */
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Log the error (in a production environment)
        error_log("Database Connection Error: " . $e->getMessage());
        throw new Exception("Database connection failed. Please try again later.");
    }
} 