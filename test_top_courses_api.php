<?php
require_once 'config.php';
require_once 'api_functions.php';

// Test the get_top_enrolled_courses function
echo "Testing get_top_enrolled_courses function...\n\n";

$start_time = microtime(true);
$top_courses = get_top_enrolled_courses(5);
$end_time = microtime(true);

if (isset($top_courses['error'])) {
    echo "Error: " . $top_courses['error'] . "\n";
} else {
    echo "Successfully retrieved " . count($top_courses) . " courses. Took " . round(($end_time - $start_time), 2) . " seconds.\n\n";
    
    if (empty($top_courses)) {
        echo "No courses found.\n";
    } else {
        echo "Top Enrolled Courses:\n";
        echo str_repeat("-", 120) . "\n";
        echo str_pad("#", 4) . " | " . 
             str_pad("ID", 6) . " | " . 
             str_pad("Enrolled", 10) . " | " . 
             str_pad("Category", 20) . " | " . 
             "Course Name\n";
        echo str_repeat("-", 120) . "\n";
        
        foreach ($top_courses as $index => $course) {
            echo str_pad(($index + 1), 4) . " | " . 
                 str_pad($course['id'], 6) . " | " . 
                 str_pad($course['enrolledusercount'] ?? 0, 10) . " | " . 
                 str_pad(substr($course['categoryname'] ?? 'Uncategorized', 0, 18), 20) . " | " . 
                 substr($course['fullname'] ?? 'Unnamed Course', 0, 60) . "\n";
        }
    }
}

// Test the API endpoint
echo "\nTesting API endpoint...\n\n";

$api_url = 'http://' . $_SERVER['HTTP_HOST'] . str_replace('test_top_courses_api.php', 'api/get_stats.php', $_SERVER['PHP_SELF']);
echo "API URL: $api_url\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    echo "Error: API returned HTTP $http_code\n";
    echo "Response: " . $response . "\n";
} else {
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "Error parsing JSON response: " . json_last_error_msg() . "\n";
        echo "Raw response: " . $response . "\n";
    } else {
        echo "API Response:\n";
        echo "Status: " . ($data['success'] ? 'Success' : 'Error') . "\n";
        
        if (isset($data['data']['top_courses'])) {
            $top_courses = $data['data']['top_courses'];
            echo "\nTop Courses from API (" . count($top_courses) . "):\n";
            foreach ($top_courses as $index => $course) {
                echo ($index + 1) . ". " . 
                     ($course['fullname'] ?? 'Unnamed Course') . 
                     " (Enrolled: " . ($course['enrolledusercount'] ?? 0) . ", " . 
                     "Category: " . ($course['categoryname'] ?? 'Uncategorized') . ")\n";
            }
        } else {
            echo "No top courses data in API response\n";
        }
        
        if (isset($data['debug'])) {
            echo "\nDebug Info:\n";
            if (isset($data['debug']['error'])) {
                echo "Error: " . $data['debug']['error'] . "\n";
            }
            if (isset($data['debug']['raw_stats'])) {
                echo "Raw Stats: " . print_r($data['debug']['raw_stats'], true) . "\n";
            }
        }
    }
}

echo "\nTest completed.\n";
