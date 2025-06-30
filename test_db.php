<?php
/**
 * Test database connection script
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load configuration
$configFile = __DIR__ . '/config/db_config.php';

if (!file_exists($configFile)) {
    die("Error: Configuration file not found at: " . $configFile);
}

$config = require $configFile;

// Display configuration (be careful with this in production)
echo "<h2>Database Configuration</h2>";
echo "<pre>";
foreach ($config as $key => $value) {
    if ($key === 'password') {
        echo "$key: " . str_repeat('*', strlen($value)) . "\n";
    } else {
        echo "$key: $value\n";
    }
}
echo "</pre>";

// Test connection
try {
    echo "<h2>Connection Test</h2>";
    
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 5,
    ];
    
    echo "<p>Attempting to connect to: mysql:host={$config['host']};dbname={$config['dbname']}</p>";
    
    $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
    
    echo "<p style='color: green;'>✅ Successfully connected to the database!</p>";
    
    // Get server info
    $serverVersion = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    $connectionStatus = $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS);
    
    echo "<h3>Server Information</h3>";
    echo "<ul>";
    echo "<li>MySQL Server Version: $serverVersion</li>";
    echo "<li>Connection Status: $connectionStatus</li>";
    
    // Test Moodle tables
    $tables = [
        'mdl_user' => "SELECT COUNT(*) as count FROM mdl_user WHERE deleted = 0 AND id > 1",
        'mdl_course' => "SELECT COUNT(*) as count FROM mdl_course WHERE id > 1",
        'mdl_user_enrolments' => "SELECT COUNT(*) as count FROM mdl_user_enrolments"
    ];
    
    echo "<h3>Table Records</h3>";
    foreach ($tables as $table => $query) {
        try {
            $stmt = $pdo->query($query);
            $result = $stmt->fetch();
            echo "<li>$table: " . number_format($result['count']) . " records</li>";
        } catch (PDOException $e) {
            echo "<li style='color: red;'>Error accessing $table: " . $e->getMessage() . "</li>";
        }
    }
    
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
    echo "<h3>Connection Failed</h3>";
    echo "<p><strong>Error Code:</strong> " . $e->getCode() . "</p>";
    echo "<p><strong>Error Message:</strong> " . $e->getMessage() . "</p>";
    
    // Common solutions based on error code
    $solutions = [
        2002 => "Check if MySQL server is running on {$config['host']}",
        1044 => "Check if the database user '{$config['username']}' has proper permissions",
        1045 => "Verify the database password is correct",
        1049 => "Check if the database '{$config['dbname']}' exists",
        2006 => "MySQL server has gone away. Try again later."
    ];
    
    echo "<h4>Possible Solutions:</h4><ul>";
    echo "<li>" . ($solutions[$e->getCode()] ?? 'Check your database configuration and server status') . "</li>";
    echo "<li>Verify the database server is accessible from this machine</li>";
    echo "<li>Check if the MySQL service is running</li>";
    echo "<li>Verify the database name, username and password</li>";
    echo "</ul>";
    
    echo "</div>";
}
