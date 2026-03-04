<?php
// =============================================================
// config/database.php — Database Connection (PDO)
// =============================================================
// PDO (PHP Data Objects) is the modern, secure way to talk to
// MySQL. It supports prepared statements, which protect against
// SQL injection attacks.
//
// We wrap the connection in a function so we can call
//   $pdo = getDB();
// from any page that needs database access.
// =============================================================

// Make sure environment variables are loaded first
require_once __DIR__ . '/env.php';

/**
 * Creates and returns a PDO database connection.
 *
 * The connection is created with these safety settings:
 *  - ERRMODE_EXCEPTION  → throw exceptions on errors (not silent)
 *  - DEFAULT_FETCH_MODE → return rows as associative arrays
 *  - EMULATE_PREPARES   → use real prepared statements (more secure)
 *
 * @return PDO
 */
function getDB(): PDO
{
    // Read credentials from environment variables
    // We never hard-code these — they live in .env
    $host = getenv('DB_HOST');
    $name = getenv('DB_NAME');
    $user = getenv('DB_USER');
    $pass = getenv('DB_PASS');

    // DSN = Data Source Name — tells PDO how to connect
    $dsn = "mysql:host=$host;dbname=$name;charset=utf8mb4";

    // PDO options configure how the connection behaves
    $options = [
        // Throw PHP exceptions when a query fails
        // Without this, errors are silent and hard to debug
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

        // Return rows as ['column' => 'value'] arrays by default
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

        // Use real prepared statements (not emulated ones)
        // This is safer against SQL injection edge-cases
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        // In production we'd log this error and show a generic message
        // For development, showing the message helps debug setup issues
        if (getenv('APP_ENV') === 'development') {
            die('Database connection failed: ' . $e->getMessage());
        }
        die('Database connection failed. Please check your configuration.');
    }
}