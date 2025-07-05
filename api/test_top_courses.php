<?php
require_once('../config.php');
require_once('../api_functions.php');

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Get top courses
try {
    $top_courses = get_top_enrolled_courses(5);
    
    if (isset($top_courses['error'])) {
        throw new Exception($top_courses['error']);
    }
    
    // Return the raw courses data
    echo json_encode([
        'success' => true,
        'data' => $top_courses,
        'count' => count($top_courses),
        'debug' => [
            'config' => [
                'api_url' => MOODLE_API_URL,
                'token' => defined('MOODLE_API_TOKEN') ? substr(MOODLE_API_TOKEN, 0, 5) . '...' : 'Not set'
            ]
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
