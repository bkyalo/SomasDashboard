<?php
require_once 'config.php';
require_once 'api_functions.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test the API connection
function test_api_connection() {
    echo "<h2>Testing Moodle API Connection</h2>";
    
    // Test 1: Basic site info
    echo "<h3>1. Testing core_webservice_get_site_info</h3>";
    $result = call_moodle_api('core_webservice_get_site_info');
    echo "<pre>" . print_r($result, true) . "</pre>";
    
    if (isset($result['error'])) {
        echo "<div style='color:red;'>Error: " . $result['error'] . "</div>";
        return false;
    }
    
    // Test 2: Get users
    echo "<h3>2. Testing core_user_get_users</h3>";
    $users = call_moodle_api('core_user_get_users', [
        'criteria' => [
            ['key' => 'deleted', 'value' => 0]
        ]
    ]);
    echo "<pre>" . print_r($users, true) . "</pre>";
    
    // Test 3: Get courses
    echo "<h3>3. Testing core_course_get_courses</h3>";
    $courses = call_moodle_api('core_course_get_courses', [
        'options' => [
            'ids' => []
        ]
    ]);
    echo "<pre>" . print_r($courses, true) . "</pre>";
    
    return true;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Moodle API Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Moodle API Connection Test</h1>
    <p>Testing connection to: <?php echo MOODLE_API_URL; ?></p>
    
    <?php 
    $start_time = microtime(true);
    $result = test_api_connection(); 
    $end_time = microtime(true);
    ?>
    
    <div style="margin-top: 20px; padding: 10px; background: #f0f0f0;">
        <strong>Execution Time:</strong> <?php echo round(($end_time - $start_time) * 1000, 2); ?> ms
    </div>
    
    <h2>PHP Info</h2>
    <p>PHP Version: <?php echo phpversion(); ?></p>
    <p>cURL Enabled: <?php echo function_exists('curl_version') ? 'Yes' : 'No'; ?></p>
    <p>JSON Extension: <?php echo extension_loaded('json') ? 'Loaded' : 'Not Loaded'; ?></p>
    
    <h2>cURL Info</h2>
    <?php 
    if (function_exists('curl_version')) {
        echo '<pre>' . print_r(curl_version(), true) . '</pre>';
    } else {
        echo 'cURL not available';
    }
    ?>
</body>
</html>
