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
$response = [
    'success' => true,
    'message' => '',
    'debug' => [],
    'data' => []
];

try {
    // Log the request
    error_log("Fetching Moodle statistics...");
    
    // Get site statistics
    error_log("Fetching site statistics...");
    $stats = get_site_statistics();
    
    // Log the statistics for debugging
    if (isset($stats['error'])) {
        throw new Exception("Failed to retrieve site statistics: " . $stats['error']);
    }
    
    // Get total number of teachers
    error_log("Fetching teacher count...");
    try {
        $teachers = call_moodle_api('core_enrol_get_enrolled_users', [
            'courseid' => 1, // Site course
            'options' => [
                'limit' => 0,
                'userfields' => 'id',
                'roleid' => 3 // Teacher role ID
            ]
        ]);
        
        $stats['total_teachers'] = is_array($teachers) ? count($teachers) : 0;
        error_log("Found " . $stats['total_teachers'] . " teachers");
        
    } catch (Exception $e) {
        error_log("Error getting teacher count: " . $e->getMessage());
        $stats['total_teachers'] = 0;
    }
    
    // Get short courses (PDC-) statistics
    error_log("Fetching short courses statistics...");
    try {
        // Get all courses
        $courses = get_all_courses_with_enrollments();
        
        // Filter PDC courses (shortname starts with 'PDC-')
        $pdcCourses = array_filter($courses, function($course) {
            return isset($course['shortname']) && stripos($course['shortname'], 'PDC-') === 0;
        });
        
        $stats['total_short_courses'] = count($pdcCourses);
        error_log("Found " . $stats['total_short_courses'] . " PDC courses");
        
        // Count total students in PDC courses
        $totalPdcStudents = 0;
        foreach ($pdcCourses as $course) {
            $totalPdcStudents += $course['enrolledusercount'] ?? 0;
        }
        $stats['total_short_course_students'] = $totalPdcStudents;
        
        // Count unique teachers in PDC courses
        $pdcTeachers = [];
        foreach ($pdcCourses as $course) {
            if (!empty($course['teacherid'])) {
                $pdcTeachers[$course['teacherid']] = true;
            }
        }
        $stats['total_short_course_teachers'] = count($pdcTeachers);
        
        error_log(sprintf(
            'PDC Courses: %d, Students: %d, Teachers: %d',
            $stats['total_short_courses'],
            $stats['total_short_course_students'],
            $stats['total_short_course_teachers']
        ));
        
    } catch (Exception $e) {
        error_log("Error getting short courses statistics: " . $e->getMessage());
        $stats['total_short_courses'] = 0;
        $stats['total_short_course_students'] = 0;
        $stats['total_short_course_teachers'] = 0;
    }
    
    // Prepare the final response
    $response['data'] = array_merge($response['data'], [
        'total_users' => $stats['total_users'] ?? 0,
        'active_users' => $stats['active_users'] ?? 0,
        'total_courses' => $stats['total_courses'] ?? 0,
        'total_teachers' => $stats['total_teachers'] ?? 0,
        'total_short_courses' => $stats['total_short_courses'] ?? 0,
        'total_short_course_students' => $stats['total_short_course_students'] ?? 0,
        'total_short_course_teachers' => $stats['total_short_course_teachers'] ?? 0,
        'total_categories' => $stats['total_categories'] ?? 0,
        'recent_activity' => [
            [
                'type' => 'info',
                'message' => 'Statistics generated successfully',
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]
    ]);
    
    $response['message'] = 'Statistics retrieved successfully';
    $response['debug'] = [
        'request_time' => date('Y-m-d H:i:s'),
        'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
        'execution_time' => round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4) . 's',
        'config' => [
            'api_url' => defined('MOODLE_API_URL') ? MOODLE_API_URL : 'Not set',
            'token_set' => !empty(MOODLE_API_TOKEN) ? 'Yes' : 'No'
        ]
    ];
    
} catch (Exception $e) {
    http_response_code(500);
    $response = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'data' => [],
        'debug' => [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'request_time' => date('Y-m-d H:i:s')
        ]
    ];
    error_log("Error in get_stats.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
}

// Log the response (without debug info to avoid sensitive data leaks)
$logResponse = $response;
if (isset($logResponse['debug'])) {
    unset($logResponse['debug']);
}
error_log("API Response: " . json_encode($logResponse, JSON_PRETTY_PRINT));

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response, JSON_PRETTY_PRINT);
?>
