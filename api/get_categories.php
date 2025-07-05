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
    'success' => false,
    'message' => '',
    'data' => []
];

function get_all_categories($parent = 0, $level = 0) {
    $categories = call_moodle_api('core_course_get_categories', [
        'criteria' => [
            ['key' => 'parent', 'value' => $parent]
        ]
    ]);
    
    $result = [];
    
    // If we got an error or empty response, return empty array
    if (empty($categories) || (is_array($categories) && isset($categories['error']))) {
        return $result;
    }
    
    // Ensure $categories is an array
    if (!is_array($categories)) {
        $categories = [$categories];
    }
    
    foreach ($categories as $category) {
        // Convert to array if it's an object
        $cat = is_object($category) ? (array)$category : $category;
        
        // Skip if we don't have required fields
        if (empty($cat['id']) || empty($cat['name'])) {
            continue;
        }
        
        $category_data = [
            'id' => (int)$cat['id'],
            'name' => str_repeat('— ', $level) . $cat['name'],
            'parent' => (int)$cat['parent'],
            'depth' => $level,
            'coursecount' => isset($cat['coursecount']) ? (int)$cat['coursecount'] : 0
        ];
        
        $result[] = $category_data;
        
        // Get subcategories recursively
        $subcategories = get_all_categories($cat['id'], $level + 1);
        if (!empty($subcategories)) {
            $result = array_merge($result, $subcategories);
        }
    }
    
    return $result;
}

try {
    // Get all categories starting from the root (parent = 0)
    $all_categories = get_all_categories(0, 0);
    
    if (empty($all_categories)) {
        throw new Exception('No categories found in the system');
    }
    
    // Sort categories by name
    usort($all_categories, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    $response = [
        'success' => true,
        'message' => 'Categories retrieved successfully',
        'data' => $all_categories
    ];
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    http_response_code(500);
    error_log('Categories Error: ' . $e->getMessage());
}

// Return JSON response
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
