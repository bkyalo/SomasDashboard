<?php
require_once('../config.php');
require_once('../api_functions.php');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Initialize response array
$response = array(
    'success' => true,
    'message' => '',
    'debug' => array(),
    'data' => array()
);

try {
    // Log the request
    error_log("Fetching Moodle statistics...");
    
    // Get site statistics
    $stats = get_site_statistics();
    
    // Add debug info
    $response['debug']['raw_stats'] = $stats;
    $response['debug']['config'] = array(
        'api_url' => MOODLE_API_URL,
        'token_set' => !empty(MOODLE_API_TOKEN) ? 'Yes' : 'No'
    );
    
    if (isset($stats['error'])) {
        throw new Exception($stats['error']);
    }
    
    // Add stats to response
    $response['data'] = $stats;
    $response['message'] = 'Data retrieved successfully';
    
    // Add sample recent activity (in a real app, this would come from Moodle logs)
    $response['data']['recent_activity'] = array(
        array(
            'type' => 'user',
            'message' => 'New user registered: johndoe',
            'timestamp' => date('Y-m-d H:i:s', strtotime('-5 minutes'))
        ),
        array(
            'type' => 'course',
            'message' => 'New course created: Advanced PHP Programming',
            'timestamp' => date('Y-m-d H:i:s', strtotime('-1 hour'))
        ),
        array(
            'type' => 'enrollment',
            'message' => '15 new enrollments in Web Development course',
            'timestamp' => date('Y-m-d H:i:s', strtotime('-3 hours'))
        )
    );
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log("Error in get_stats.php: " . $e->getMessage());
}

// Log the response for debugging
error_log("API Response: " . print_r($response, true));

// Return JSON response
echo json_encode($response, JSON_PRETTY_PRINT);
?>
