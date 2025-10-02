<?php

/**
 * -------------------------------------------------------------------------
 * Database Connection Details
 * -------------------------------------------------------------------------
 * This file contains the configuration settings for connecting to the
 * MySQL database. It should be included in any PHP script that needs
 * to access the database.
 *
 * It is crucial to keep this file secure and not expose it publicly.
 */

// Define database connection constants
define('DB_HOST', 'sql301.infinityfree.com'); // MySQL Hostname
define('DB_USER', 'if0_38435297');          // MySQL Username
define('DB_PASS', 'l0Hwi1fBF20');           // MySQL Password
define('DB_NAME', 'if0_38435297_calendar'); // MySQL Database Name
define('DB_PORT', 3306);                     // MySQL Port (optional)
define('DB_CHARSET', 'utf8mb4');             // Character set for the connection

/**
 * -------------------------------------------------------------------------
 * Create Database Connection (PDO)
 * -------------------------------------------------------------------------
 * The following code creates a new PDO object to connect to the database.
 * PDO (PHP Data Objects) is the recommended way to access databases in PHP
 * as it helps prevent SQL injection attacks.
 */

// Data Source Name (DSN)
$dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

// PDO Options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Create the PDO instance
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (\PDOException $e) {
    // If connection fails, stop the script and show an error message.
    // In a real production environment, you might want to log this error
    // instead of showing it to the user.
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// The $pdo variable is now ready to be used for database queries.
?>
