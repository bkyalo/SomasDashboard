<?php
require_once('../config.php');
require_once('../api_functions.php');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Get categories with parent ID 0 (top-level categories)
$categories = call_moodle_api('core_course_get_categories', [
    'criteria' => [
        ['key' => 'parent', 'value' => 0]
    ]
]);

// Output the raw response
echo json_encode([
    'success' => true,
    'debug' => [
        'categories_type' => gettype($categories),
        'is_array' => is_array($categories),
        'is_object' => is_object($categories),
        'categories_count' => is_array($categories) ? count($categories) : 'N/A',
        'first_item' => is_array($categories) && !empty($categories) ? $categories[0] : 'No items',
        'raw_response' => $categories
    ]
], JSON_PRETTY_PRINT);
?>
