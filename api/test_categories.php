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
    // Test 1: Try to get categories directly
    $categories = call_moodle_api('core_course_get_categories', [
        'criteria' => [
            ['key' => 'parent', 'value' => 0] // Get top-level categories
        ]
    ]);
    
    // Test 2: Get all courses to see their category data
    $courses = call_moodle_api('core_course_get_courses', [
        'options' => [
            'ids' => [] // Get all courses
        ]
    ]);
    
    // Prepare response
    $response = [
        'success' => true,
        'categories_direct' => $categories,
        'courses_sample' => array_slice($courses, 0, 3), // Show first 3 courses with their category data
        'category_keys' => [],
        'courses_category_field' => []
    ];
    
    // Check what fields are available in the categories
    if (!empty($categories) && is_array($categories)) {
        $first_cat = reset($categories);
        if ($first_cat) {
            $response['category_keys'] = array_keys($first_cat);
        }
    }
    
    // Check what category fields are available in courses
    if (!empty($courses) && is_array($courses)) {
        $first_course = reset($courses);
        if ($first_course) {
            $response['courses_category_field'] = [
                'has_category' => isset($first_course['category']),
                'has_categoryid' => isset($first_course['categoryid']),
                'has_categoryname' => isset($first_course['categoryname']),
                'all_keys' => array_keys($first_course)
            ];
        }
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
