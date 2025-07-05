<?php
/**
 * Moodle Analytics Dashboard Configuration
 * 
 * Copy this file to config.php and update the values below with your Moodle site details.
 */

// Moodle API Configuration
define('MOODLE_API_URL', 'https://your-moodle-site.com/webservice/rest/server.php');
define('MOODLE_API_TOKEN', 'your_webservice_token_here');

// Database Configuration (if needed)
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'moodle');
// define('DB_USER', 'moodleuser');
// define('DB_PASS', 'your_db_password');

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Application Settings
define('DEBUG_MODE', true);

// CORS Headers (if needed)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Timezone
date_default_timezone_set('UTC');

// Session Configuration (if needed)
// session_start();
