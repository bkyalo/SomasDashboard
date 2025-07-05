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
    error_log("Fetching site statistics...");
    $stats = get_site_statistics();
    
    // Log the statistics for debugging
    if (isset($stats['error'])) {
        error_log("Error getting site statistics: " . $stats['error']);
        throw new Exception("Failed to retrieve site statistics: " . $stats['error']);
    }
    
    error_log("Site statistics retrieved: " . print_r($stats, true));
    
    // Get top enrolled courses
    error_log("Fetching top enrolled courses...");
    $top_courses = get_top_enrolled_courses(5);
    
    if (isset($top_courses['error'])) {
        $error_message = "Error getting top courses: " . $top_courses['error'];
        error_log($error_message);
        // Instead of failing the whole request, we'll return an empty array for top_courses
        // but still include the error in the debug info
        $top_courses_data = [];
        $response['debug']['top_courses_error'] = $top_courses['error'];
    } else {
        error_log("Successfully retrieved " . count($top_courses) . " top courses");
        $top_courses_data = $top_courses;
    }

    // Prepare response with all available data
    $response = [
        'success' => true,
        'data' => [
            'total_users' => $stats['total_users'] ?? 0,
            'active_users' => $stats['active_users'],
            'total_courses' => $stats['total_courses'],
            'total_categories' => $stats['total_categories'] ?? 0,
            'top_courses' => $top_courses_data
        ],
        'timestamp' => date('Y-m-d H:i:s'),
        'debug' => [
            'raw_stats' => $stats,
            'config' => [
                'api_url' => MOODLE_API_URL,
                'token_set' => !empty(MOODLE_API_TOKEN) ? 'Yes' : 'No'
            ]
        ]
    ];
    
    if (isset($stats['error'])) {
        throw new Exception($stats['error']);
    }
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
