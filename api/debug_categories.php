<?php
require_once('../config.php');
require_once('../api_functions.php');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    // Get all courses
    $courses = call_moodle_api('core_course_get_courses', [
        'options' => [
            'ids' => [] // Get all courses
        ]
    ]);
    
    // Get all categories
    $categories = [];
    $categories_result = call_moodle_api('core_course_get_categories', [
        'addsubcategories' => 1
    ]);
    
    if (!empty($categories_result) && !isset($categories_result['error'])) {
        foreach ($categories_result as $category) {
            $categories[$category['id']] = $category;
        }
    }
    
    // Prepare response
    $response = [
        'success' => true,
        'courses_sample' => [],
        'categories_count' => count($categories),
        'courses_count' => count($courses),
        'category_fields' => !empty($categories) ? array_keys(reset($categories)) : []
    ];
    
    // Add first few courses with their category info
    $sample = array_slice($courses, 0, 3);
    foreach ($sample as $course) {
        $category_id = $course['category'] ?? 0;
        $response['courses_sample'][] = [
            'id' => $course['id'],
            'fullname' => $course['fullname'] ?? '',
            'category_id' => $category_id,
            'category_info' => $categories[$category_id] ?? null,
            'all_fields' => array_keys($course)
        ];
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
