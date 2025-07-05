<?php
require_once('../config.php');
require_once('../api_functions.php');

header('Content-Type: application/json');

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Get query parameters
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
    
    // Get all courses with enrollment data
    $allCourses = get_all_courses_with_enrollments();
    
    // Apply search filter
    if (!empty($search)) {
        $search = strtolower($search);
        $allCourses = array_filter($allCourses, function($course) use ($search) {
            return (strpos(strtolower($course['fullname']), $search) !== false) ||
                   (strpos(strtolower($course['shortname']), $search) !== false) ||
                   (strpos(strtolower($course['categoryname']), $search) !== false) ||
                   ($course['teacher'] && strpos(strtolower($course['teacher']), $search) !== false);
        });
        // Re-index array after filtering
        $allCourses = array_values($allCourses);
    }
    
    // Calculate pagination
    $totalCourses = count($allCourses);
    $totalPages = ceil($totalCourses / $perPage);
    $offset = ($page - 1) * $perPage;
    $paginatedCourses = array_slice($allCourses, $offset, $perPage);
    
    // Prepare response
    $response = [
        'success' => true,
        'data' => $paginatedCourses,
        'pagination' => [
            'total' => $totalCourses,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $totalPages,
            'from' => $totalCourses > 0 ? $offset + 1 : 0,
            'to' => min($offset + $perPage, $totalCourses)
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while fetching courses: ' . $e->getMessage()
    ]);
}
