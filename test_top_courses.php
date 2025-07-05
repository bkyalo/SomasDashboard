<?php
require_once 'config.php';
require_once 'api_functions.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Testing Top Enrolled Courses</h1>";

// Test the function
$start_time = microtime(true);
$top_courses = get_top_enrolled_courses(5);
$end_time = microtime(true);

if (isset($top_courses['error'])) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px;'>";
    echo "<h2>Error:</h2>";
    echo "<pre>" . htmlspecialchars(print_r($top_courses, true)) . "</pre>";
    echo "</div>";
} else {
    echo "<div style='margin: 20px;'>";
    echo "<h2>Top 5 Enrolled Courses (Fetched in " . round(($end_time - $start_time), 2) . " seconds)</h2>";
    
    if (empty($top_courses)) {
        echo "<p>No courses found.</p>";
    } else {
        echo "<div style='display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;'>";
        foreach ($top_courses as $course) {
            echo "<div style='border: 1px solid #ddd; padding: 15px; border-radius: 5px;'>";
            echo "<h3>" . htmlspecialchars($course['fullname']) . "</h3>";
            echo "<p><strong>ID:</strong> " . htmlspecialchars($course['id']) . "</p>";
            echo "<p><strong>Shortname:</strong> " . htmlspecialchars($course['shortname']) . "</p>";
            echo "<p><strong>Category:</strong> " . htmlspecialchars($course['categoryname']) . " (ID: " . htmlspecialchars($course['categoryid']) . ")</p>";
            echo "<p><strong>Enrolled Users:</strong> " . number_format($course['enrolledusercount']) . "</p>";
            echo "<img src='" . htmlspecialchars($course['courseimage']) . "' style='max-width: 100%; height: auto;'>";
            echo "</div>";
        }
        echo "</div>";
    }
    echo "</div>";
}

// Show the raw response for debugging
echo "<div style='margin: 20px; padding: 10px; background: #f5f5f5; border: 1px solid #ddd;'>";
echo "<h3>Raw Response:</h3>";
echo "<pre>" . htmlspecialchars(print_r($top_courses, true)) . "</pre>";
echo "</div>";

// Check error log
echo "<div style='margin: 20px; padding: 10px; background: #f0f0f0; border: 1px solid #ccc;'>";
echo "<h3>Error Log:</h3>";
$error_log = ini_get('error_log');
if (file_exists($error_log)) {
    $log_content = file_get_contents($error_log);
    echo "<pre>" . htmlspecialchars($log_content) . "</pre>";
} else {
    echo "<p>Error log not found at: " . htmlspecialchars($error_log) . "</p>";
}
echo "</div>";
