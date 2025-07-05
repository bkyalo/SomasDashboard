<?php
require_once 'config.php';
require_once 'api_functions.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>Testing Moodle API connection...\n";

// Test 1: Get site info
$siteinfo = call_moodle_api('core_webservice_get_site_info', []);
echo "\n=== Site Info ===\n";
print_r($siteinfo);

// Test 2: Get courses
$courses = call_moodle_api('core_course_get_courses_by_field', [
    'field' => '',
    'value' => ''
]);

echo "\n\n=== Courses ===\n";
print_r($courses);

// If we have courses, try to get enrolled users for the first course
if (!empty($courses['courses']) && is_array($courses['courses'])) {
    $firstCourse = reset($courses['courses']);
    $courseId = $firstCourse['id'] ?? 0;
    
    if ($courseId) {
        echo "\n\n=== Enrolled Users for Course ID: $courseId ===\n";
        $enrolled = call_moodle_api('core_enrol_get_enrolled_users', [
            'courseid' => $courseId
        ]);
        print_r($enrolled);
    }
}

echo "</pre>";
