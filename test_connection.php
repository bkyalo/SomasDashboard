<?php
/**
 * Test script for Moodle database connection
 */

// Load the database configuration
$config = require_once __DIR__ . '/config/db_config.php';

// Load the database connection class
require_once __DIR__ . '/MoodleDBConnection.php';

try {
    echo "Attempting to connect to the database...\n";
    
    // Create a new database connection
    $db = new MoodleDBConnection(
        $config['host'],
        $config['dbname'],
        $config['username'],
        $config['password']
    );
    
    // Get the PDO connection
    $pdo = $db->connect();
    
    echo "✅ Successfully connected to the database!\n";
    
    // Test query to get some basic information
    $stmt = $pdo->query("SELECT DATABASE() as dbname, USER() as user, VERSION() as version");
    $info = $stmt->fetch();
    
    echo "\nDatabase Information:\n";
    echo "- Database: " . $info['dbname'] . "\n";
    echo "- User: " . $info['user'] . "\n";
    echo "- MySQL Version: " . $info['version'] . "\n";
    
    // Test query to check if Moodle tables exist
    echo "\nChecking for Moodle tables...\n";
    $tables = $pdo->query("SHOW TABLES LIKE 'mdl_%'");
    $tableCount = $tables->rowCount();
    
    if ($tableCount > 0) {
        echo "✅ Found $tableCount Moodle tables\n";
    } else {
        echo "⚠️ No Moodle tables found (mdl_ prefix)\n";
    }
    
    // Close the connection
    $db->close();
    
} catch (PDOException $e) {
    die("❌ Connection failed: " . $e->getMessage() . "\n");
}

echo "\nTest completed.\n";
