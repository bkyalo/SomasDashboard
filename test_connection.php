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
    
    // Test 2: Get available web services
    echo "<h3>2. Testing core_webservice_get_site_info for available functions</h3>";
    $functions = call_moodle_api('core_webservice_get_site_info', ['serviceshortnames' => ['moodle_webservice_restful']]);
    echo "<pre>" . print_r($functions, true) . "</pre>";
    
    // Test 3: Get users (first 5)
    echo "<h3>3. Testing core_enrol_get_enrolled_users (first 5)</h3>";
    $users = call_moodle_api('core_enrol_get_enrolled_users', [
        'courseid' => 1, // Site course
        'options[0][name]' => 'limitfrom',
        'options[0][value]' => 0,
        'options[1][name]' => 'limitnumber',
        'options[1][value]' => 5
    ]);
    echo "<pre>" . print_r($users, true) . "</pre>";
    
    return true;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Moodle API Test Connection</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        pre { 
            background: #f5f5f5; 
            padding: 10px; 
            border: 1px solid #ddd; 
            border-radius: 4px;
            max-height: 300px;
            overflow: auto;
        }
        .success { color: green; }
        .error { color: red; }
        .info { 
            background: #e7f3fe;
            border-left: 6px solid #2196F3;
            padding: 10px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <h1>Moodle API Connection Test</h1>
    <div class="info">
        <p><strong>API URL:</strong> <?php echo htmlspecialchars(MOODLE_API_URL); ?></p>
        <p><strong>Token Status:</strong> <?php echo !empty(MOODLE_API_TOKEN) ? '✅ Set' : '❌ Not Set'; ?></p>
        <?php if (!empty(MOODLE_API_TOKEN)): ?>
            <p><strong>Token:</strong> <?php echo substr(MOODLE_API_TOKEN, 0, 6) . '...' . substr(MOODLE_API_TOKEN, -4); ?></p>
        <?php endif; ?>
    </div>
    
    <?php 
    $start_time = microtime(true);
    $result = test_api_connection(); 
    $end_time = microtime(true);
    ?>
    
    <div style="margin-top: 20px; padding: 10px; background: #f0f0f0;">
        <p><strong>Execution Time:</strong> <?php echo round(($end_time - $start_time) * 1000, 2); ?> ms</p>
        <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
        <p><strong>cURL Enabled:</strong> <?php echo function_exists('curl_version') ? '✅ Yes' : '❌ No'; ?></p>
        <p><strong>JSON Extension:</strong> <?php echo extension_loaded('json') ? '✅ Loaded' : '❌ Not Loaded'; ?></p>
    </div>
    
    <h2>cURL Info</h2>
    <?php 
    if (function_exists('curl_version')) {
        echo '<pre>' . print_r(curl_version(), true) . '</pre>';
    } else {
        echo '<p>cURL is not available on this server.</p>';
    }
    ?>
    
    <h2>PHP Info</h2>
    <p><a href="phpinfo.php" target="_blank">View phpinfo()</a> (opens in new tab)</p>
</body>
</html>
