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
    $categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
    
    // Get all courses with enrollment data
    $allCourses = get_all_courses_with_enrollments();
    
    // Debug log the filter parameters
    error_log('Filtering courses - Search: ' . $search . ', Category ID: ' . $categoryId);
    
    // Apply filters
    $allCourses = array_filter($allCourses, function($course) use ($search, $categoryId) {
        $matches = true;
        
        // Apply search filter
        if (!empty($search)) {
            $search = strtolower($search);
            $matches = $matches && (
                (isset($course['fullname']) && strpos(strtolower($course['fullname']), $search) !== false) ||
                (isset($course['shortname']) && strpos(strtolower($course['shortname']), $search) !== false) ||
                (isset($course['categoryname']) && strpos(strtolower($course['categoryname']), $search) !== false) ||
                (isset($course['teacher']) && $course['teacher'] && strpos(strtolower($course['teacher']), $search) !== false)
            );
        }
        
        // Apply category filter
        if ($categoryId > 0) {
            // Get the course's category ID, handling both 'categoryid' and 'category' fields
            $courseCategoryId = $course['categoryid'] ?? $course['category'] ?? null;
            
            // Debug log the course's category information
            error_log('Checking course: ' . ($course['fullname'] ?? 'unknown') . ' - Category ID: ' . ($courseCategoryId ?? 'undefined'));
            
            // Check if the course belongs to the selected category
            // Convert both to integers for comparison to ensure type safety
            $categoryMatch = ($courseCategoryId !== null && (int)$courseCategoryId === (int)$categoryId);
            
            error_log('Category match for course ' . ($course['id'] ?? 'unknown') . ': ' . ($categoryMatch ? 'YES' : 'NO'));
            
            $matches = $matches && $categoryMatch;
        }
        
        return $matches;
    });
    
    error_log('Filtered courses count: ' . count($allCourses));
    
    // Re-index array after filtering
    $allCourses = array_values($allCourses);
    
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
