<?php
require_once('config.php');

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Make a REST call to Moodle API
 */
function call_moodle_api($functionname, $params = []) {
    // Add required Moodle web service parameters
    $params['wstoken'] = MOODLE_API_TOKEN;
    $params['wsfunction'] = $functionname;
    $params['moodlewsrestformat'] = 'json';
    
    // Format parameters for Moodle's web service
    $post_data = [];
    foreach ($params as $key => $value) {
        if (is_array($value)) {
            // Handle array parameters (like options)
            foreach ($value as $i => $item) {
                if (is_array($item)) {
                    foreach ($item as $k => $v) {
                        $post_data["$key" . "[$i]" . "[$k]"] = $v;
                    }
                } else {
                    $post_data["$key" . "[$i]"] = $item;
                }
            }
        } else {
            $post_data[$key] = $value;
        }
    }
    
    // Build the query string
    $query_string = http_build_query($post_data, '', '&');
    
    // Log the request
    error_log("\n=== Moodle API Request ===");
    error_log("Function: $functionname");
    error_log("URL: " . MOODLE_API_URL);
    error_log("Params: " . print_r($post_data, true));
    
    // Initialize cURL
    $ch = curl_init();
    if ($ch === false) {
        $error = 'Failed to initialize cURL';
        error_log($error);
        return ['error' => $error];
    }
    
    curl_setopt($ch, CURLOPT_URL, MOODLE_API_URL);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification (for testing only)
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // Disable host verification (for testing only)
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 seconds timeout
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // 10 seconds connection timeout
    
    // Log the full request for debugging
    error_log("cURL options set. Making request...");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification for now
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'User-Agent: Moodle Analytics Dashboard/1.0'
    ]);
    
    // Execute the request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    $curl_errno = curl_errno($ch);
    
    // Log response details
    error_log("cURL HTTP Code: " . $http_code);
    error_log("cURL Error Number: " . $curl_errno);
    error_log("cURL Error: " . $curl_error);
    
    // Check for cURL errors
    if ($curl_errno) {
        $error_msg = "cURL Error #$curl_errno: $curl_error";
        error_log($error_msg);
        
        // Log additional cURL info if available
        $curl_info = curl_getinfo($ch);
        error_log("cURL Info: " . print_r($curl_info, true));
        
        if (isset($curl_info['ssl_verify_result']) && $curl_info['ssl_verify_result'] !== 0) {
            error_log("SSL Verification failed with code: " . $curl_info['ssl_verify_result']);
        }
        
        @curl_close($ch);
        return ['error' => $error_msg];
    }
    
    // Log the raw response (first 1000 chars to avoid huge logs)
    $response_log = is_string($response) ? substr($response, 0, 1000) : gettype($response);
    error_log("API Response (truncated): " . $response_log);
    
    // Close cURL resource
    @curl_close($ch);
    
    // Check if we got a valid response
    if ($response === false) {
        $error_msg = 'Empty response from server';
        error_log($error_msg);
        return ['error' => $error_msg];
    }
    
    // Handle non-200 HTTP status codes
    if ($http_code < 200 || $http_code >= 300) {
        $error_msg = "HTTP Error: $http_code";
        error_log($error_msg);
        return ['error' => $error_msg, 'http_code' => $http_code];
    }
    
    // Decode the JSON response
    $decoded_response = json_decode($response, true);
    
    // Check for JSON decode errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        $error_msg = 'JSON decode error: ' . json_last_error_msg() . ' (Code: ' . json_last_error() . ')';
        error_log($error_msg);
        error_log("Response that caused JSON error: " . $response);
        return ['error' => $error_msg, 'raw_response' => $response];
    }
    
    // Log if we got an error from the Moodle API
    if (isset($decoded_response['exception'])) {
        $error_message = "Moodle Exception: " . ($decoded_response['message'] ?? 'Unknown error');
        $debug_info = $decoded_response['debuginfo'] ?? 'No debug info';
        
        error_log("Moodle API Exception: $error_message");
        error_log("Debug Info: $debug_info");
        
        return [
            'error' => $error_message,
            'debuginfo' => $debug_info,
            'exception' => true
        ];
    }
    
    // Check for error message in the response
    if (isset($decoded_response['error'])) {
        $error_message = "Moodle Error: " . $decoded_response['error'];
        error_log("ERROR: $error_message");
        return ['error' => $error_message];
    }
    
    error_log("API Call Successful");
    return $decoded_response;
}

/**
 * Get top enrolled courses with their details
 * @param int $limit Number of courses to return
 * @return array Array of courses with their details
 */
function get_top_enrolled_courses($limit = 5) {
    try {
        error_log("Getting top $limit enrolled courses...");
        
        // Initialize categories array
        $categories = [];
        
        try {
            // First, try to get categories using core_course_get_categories
            error_log("Fetching course categories...");
            $categories_result = call_moodle_api('core_course_get_categories', [
                'criteria' => [
                    ['key' => 'parent', 'value' => 0] // Get top-level categories
                ]
            ]);
            
            // Log the raw categories response for debugging
            error_log("Categories API response: " . print_r($categories_result, true));
            
            if (!empty($categories_result) && !isset($categories_result['error'])) {
                if (isset($categories_result['exception'])) {
                    error_log("Exception in categories API: " . $categories_result['message']);
                } else {
                    foreach ($categories_result as $category) {
                        if (isset($category['id']) && isset($category['name'])) {
                            $categories[$category['id']] = $category['name'];
                        }
                    }
                }
            }
            
            // If no categories found, try alternative method
            if (empty($categories)) {
                error_log("No categories found with core_course_get_categories, trying alternative method...");
                $all_courses = call_moodle_api('core_course_get_courses', []);
                
                if (is_array($all_courses)) {
                    foreach ($all_courses as $course) {
                        if (isset($course['category']) && $course['category'] > 0) {
                            // Try to get category name directly from course data if available
                            if (isset($course['categoryname'])) {
                                $categories[$course['category']] = $course['categoryname'];
                            }
                        }
                    }
                }
            }
            
            error_log("Fetched " . count($categories) . " course categories");
            
        } catch (Exception $e) {
            error_log("Error fetching categories: " . $e->getMessage());
            // Continue without categories rather than failing the entire request
        }
        
        // Get all courses with their enrollment counts
        error_log("Fetching all courses...");
        $courses = call_moodle_api('core_course_get_courses', [
            'options' => [
                'ids' => [] // Empty array means get all courses
            ]
        ]);
        
        // Log the raw courses response for debugging
        error_log("Courses API response: " . print_r($courses, true));
        
        if (empty($courses)) {
            $error_msg = "No courses returned from API";
            error_log($error_msg);
            return ['error' => $error_msg];
        }
        
        if (isset($courses['error'])) {
            $error_msg = "Error getting courses: " . $courses['error'];
            error_log($error_msg);
            return ['error' => $error_msg];
        }
        
        if (isset($courses['exception'])) {
            $error_msg = "Exception in courses API: " . ($courses['message'] ?? 'Unknown error');
            error_log($error_msg);
            return ['error' => $error_msg];
        }
        
        error_log("Successfully retrieved " . count($courses) . " courses");
        
        // Process the courses
        $result = [];
        foreach ($courses as $course) {
            // Skip if invalid course or course ID is 1
            if (!is_array($course) || empty($course['id']) || $course['id'] == 1) {
                continue;
            }
            
            try {
                // Get enrollment count for this course
                error_log("Fetching enrolled users for course ID: " . $course['id']);
                $enrolled_users = call_moodle_api('core_enrol_get_enrolled_users', [
                    'courseid' => $course['id']
                ]);
                
                // Log the enrolled users response for debugging
                error_log("Enrolled users response for course " . $course['id'] . ": " . print_r($enrolled_users, true));
                
                // Handle potential errors in the enrolled users response
                if (isset($enrolled_users['error'])) {
                    error_log("Error fetching enrolled users for course " . $course['id'] . ": " . $enrolled_users['error']);
                    $enrolled_count = 0;
                } else if (isset($enrolled_users['exception'])) {
                    error_log("Exception fetching enrolled users for course " . $course['id'] . ": " . ($enrolled_users['message'] ?? 'Unknown error'));
                    $enrolled_count = 0;
                } else if (!is_array($enrolled_users)) {
                    error_log("Unexpected response format for enrolled users in course " . $course['id']);
                    $enrolled_count = 0;
                } else {
                    $enrolled_count = count($enrolled_users);
                    error_log("Found $enrolled_count enrolled users for course " . $course['id']);
                }
                
                // Get category information
                $category_id = $course['category'] ?? 0;
                $category_name = 'Uncategorized';
                
                // Try to get category from the fetched categories
                if ($category_id > 0 && !empty($categories[$category_id])) {
                    $category_name = $categories[$category_id]['name'];
                } 
                // If not found, try to get it from the course data directly
                // If not found, try to get it from the course data
                else if (isset($course['categoryname'])) {
                    $category_name = $course['categoryname'];
                    $category_id = $course['category'] ?? $category_id;
                }
                
                $result[] = [
                    'id' => $course['id'],
                    'fullname' => $course['fullname'] ?? 'Unnamed Course',
                    'shortname' => $course['shortname'] ?? '',
                    'enrolledusercount' => $enrolled_count,
                    'categoryid' => $category_id,
                    'categoryname' => $category_name,
                    'courseimage' => $course['courseimage'] ?? 'https://via.placeholder.com/400x200?text=No+Image',
                    'summary' => $course['summary'] ?? ''
                ];
                
                error_log("Added course ID: " . $course['id'] . " with " . $enrolled_count . " enrolled users");
                
            } catch (Exception $e) {
                error_log("Error processing course ID " . ($course['id'] ?? 'unknown') . ": " . $e->getMessage());
                continue;
            }
        }
        
        // Sort by enrolled user count in descending order
        usort($result, function($a, $b) {
            return $b['enrolledusercount'] - $a['enrolledusercount'];
        });
        
        // Return only the top $limit courses
        return array_slice($result, 0, $limit);
        
    } catch (Exception $e) {
        error_log("Exception in get_top_enrolled_courses: " . $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get site statistics
 */
function get_site_statistics() {
    try {
        $stats = [];
        
        // First, get site info to verify connection
        error_log("=== Testing Moodle API Connection ===");
        $siteinfo = call_moodle_api('core_webservice_get_site_info');
        
        // Log the full response for debugging
        error_log("Site Info Response: " . print_r($siteinfo, true));
        
        if (isset($siteinfo['error'])) {
            error_log("❌ Error connecting to Moodle API: " . print_r($siteinfo['error'], true));
            return ['error' => 'Failed to connect to Moodle API: ' . $siteinfo['error']];
        }
        
        if (empty($siteinfo)) {
            error_log("❌ Empty response from Moodle API");
            return ['error' => 'Empty response from Moodle API'];
        }
        
        error_log("✅ Successfully connected to Moodle API");
        error_log("Site Name: " . ($siteinfo['sitename'] ?? 'N/A'));
        error_log("Moodle Version: " . ($siteinfo['version'] ?? 'N/A'));
        error_log("User ID: " . ($siteinfo['userid'] ?? 'N/A'));
        error_log("User Count: " . ($siteinfo['usercount'] ?? 'N/A'));
        
        // Get total user count using core_enrol_get_enrolled_users with pagination
        error_log("Getting total user count...");
        $stats['total_users'] = 0;
        $perpage = 1000;
        $page = 0;
        $has_more = true;
        
        // First try to get from site info which is faster
        $site_info = call_moodle_api('core_webservice_get_site_info');
        if (isset($site_info['usercount'])) {
            $stats['total_users'] = (int)$site_info['usercount'];
            error_log("Got user count from site info: " . $stats['total_users']);
        } else {
            // Fallback to paginated user count if site info fails
            error_log("Falling back to paginated user count...");
            while ($has_more) {
                $users = call_moodle_api('core_enrol_get_enrolled_users', [
                    'courseid' => 1, // Site course
                    'options' => [
                        ['name' => 'limitfrom', 'value' => $page * $perpage],
                        ['name' => 'limitnumber', 'value' => $perpage]
                    ]
                ]);
                
                if (isset($users['error'])) {
                    error_log("Error getting users: " . $users['error']);
                    $stats['total_users'] = 'Error: ' . $users['error'];
                    break;
                }
                
                if (empty($users) || !is_array($users)) {
                    $has_more = false;
                } else {
                    $count = count($users);
                    $stats['total_users'] += $count;
                    $page++;
                    
                    // If we got fewer users than requested, we've reached the end
                    if ($count < $perpage) {
                        $has_more = false;
                    }
                    
                    // Prevent infinite loops
                    if ($page > 100) {
                        error_log("Warning: Reached maximum page limit for user pagination");
                        break;
                    }
                }
            }
        }
        
        // Get number of courses - using core_course_get_courses_by_field
        error_log("Getting courses...");
        $courses = call_moodle_api('core_course_get_courses_by_field', [
            'field' => '',
            'value' => ''
        ]);
        
        if (isset($courses['error'])) {
            error_log("Error getting courses: " . $courses['error']);
            $stats['total_courses'] = 'Error: ' . $courses['error'];
        } else {
            $stats['total_courses'] = is_array($courses['courses'] ?? null) ? count($courses['courses']) : 0;
        }
        
        // Get number of categories - using core_course_get_categories
        error_log("Getting categories...");
        $categories = call_moodle_api('core_course_get_categories', [
            'criteria[0][key]' => 'visible',
            'criteria[0][value]' => 1,
            'addsubcategories' => 0
        ]);
        
        if (isset($categories['error'])) {
            error_log("Error getting categories: " . $categories['error']);
            $stats['total_categories'] = 'Error: ' . $categories['error'];
        } else {
            $stats['total_categories'] = is_array($categories) ? count($categories) : 0;
        }
        
        // Get active users in last 60 minutes using report_log_get_recent_activity
        error_log("Getting active users...");
        $one_hour_ago = time() - 3600;
        $active_users = call_moodle_api('report_log_get_recent_activity', [
            'courseid' => 1, // Site course
            'limit' => 1000,
            'since' => $one_hour_ago
        ]);
        
        if (isset($active_users['error'])) {
            error_log("Error getting active users: " . $active_users['error']);
            
            // Fallback to core_enrol_get_enrolled_users if report_log fails
            error_log("Falling back to core_enrol_get_enrolled_users for active users...");
            $active_users = call_moodle_api('core_enrol_get_enrolled_users', [
                'courseid' => 1, // Site course
                'options' => [
                    ['name' => 'limitnumber', 'value' => 1000],
                    ['name' => 'userfields', 'value' => 'id,lastaccess']
                ]
            ]);
            
            if (isset($active_users['error'])) {
                $stats['active_users'] = 'Error: ' . $active_users['error'];
            } else {
                // Count users with lastaccess in the last hour
                $active_count = 0;
                if (is_array($active_users)) {
                    foreach ($active_users as $user) {
                        if (isset($user['lastaccess']) && $user['lastaccess'] >= $one_hour_ago) {
                            $active_count++;
                        }
                    }
                }
                $stats['active_users'] = $active_count;
            }
        } else {
            // Count unique users from the logs
            $active_user_ids = [];
            if (isset($active_users['logs']) && is_array($active_users['logs'])) {
                foreach ($active_users['logs'] as $log) {
                    if (isset($log['userid']) && $log['userid'] > 0) {
                        $active_user_ids[$log['userid']] = true;
                    }
                }
                $stats['active_users'] = count($active_user_ids);
            } else {
                $stats['active_users'] = 0;
            }
        }
        
    } catch (Exception $e) {
        error_log("Exception in get_site_statistics: " . $e->getMessage());
        $stats['error'] = 'Exception: ' . $e->getMessage();
    }
    
    error_log("Final stats: " . print_r($stats, true));
    return $stats;
}

/**
 * Get all courses with enrollment counts and teacher information
 * @return array Array of courses with their details
 */
function get_all_courses_with_enrollments() {
    try {
        // Get all courses
        $courses = call_moodle_api('core_course_get_courses', []);
        
        if (empty($courses) || isset($courses['error'])) {
            log_error('Failed to fetch courses', $courses);
            return [];
        }
        
        // Get all categories with more detailed information
        $categories = [];
        try {
            $categories_result = call_moodle_api('core_course_get_categories', [
                'addsubcategories' => 1,
                'criteria' => [
                    ['key' => 'ids', 'value' => ''] // Get all categories
                ]
            ]);
            
            if (!empty($categories_result) && !isset($categories_result['error'])) {
                foreach ($categories_result as $category) {
                    if (isset($category['id'])) {
                        $categories[$category['id']] = [
                            'name' => $category['name'] ?? 'Uncategorized',
                            'idnumber' => $category['idnumber'] ?? '',
                            'parent' => $category['parent'] ?? 0
                        ];
                    }
                }
                error_log("Fetched " . count($categories) . " course categories");
            } else {
                error_log("No categories found or error in response");
            }
        } catch (Exception $e) {
            error_log("Error fetching categories: " . $e->getMessage());
            // Continue with empty categories
        }
        
        $result = [];
        
        foreach ($courses as $course) {
            // Skip the front page course (usually ID 1)
            if ($course['id'] == 1) {
                continue;
            }
            
            // Get enrolled users
            $enrolled_users = [];
            try {
                $enrolled_users = call_moodle_api('core_enrol_get_enrolled_users', [
                    'courseid' => $course['id'],
                    'options' => [
                        ['name' => 'onlyactive', 'value' => 1]
                    ]
                ]);
            } catch (Exception $e) {
                log_error("Error getting enrolled users for course {$course['id']}: " . $e->getMessage());
            }
            
            // Count students and find teachers
            $student_count = 0;
            $teachers = [];
            
            foreach ($enrolled_users as $user) {
                foreach ($user['roles'] as $role) {
                    if ($role['shortname'] === 'editingteacher' || $role['shortname'] === 'teacher') {
                        $teachers[] = $user['fullname'];
                        break;
                    }
                }
                if (empty($user['roles']) || 
                    (isset($user['roles'][0]['shortname']) && $user['roles'][0]['shortname'] === 'student')) {
                    $student_count++;
                }
            }
            
            // Handle category information with multiple fallbacks
            $categoryId = $course['category'] ?? 0;
            $categoryName = 'Uncategorized';
            
            // Try to get category from the fetched categories
            if ($categoryId > 0 && !empty($categories[$categoryId])) {
                if (is_array($categories[$categoryId])) {
                    $categoryName = $categories[$categoryId]['name'] ?? 'Uncategorized';
                } else {
                    $categoryName = $categories[$categoryId];
                }
            } 
            // Fallback to course's categoryname if available
            else if (!empty($course['categoryname'])) {
                $categoryName = $course['categoryname'];
            }
            // Fallback to course's displayname format (some Moodle versions use 'Category: Course Name')
            else if (!empty($course['displayname'])) {
                $parts = explode(':', $course['displayname'], 2);
                if (count($parts) > 1) {
                    $categoryName = trim($parts[0]);
                }
            }
            
            // If still uncategorized, try to get parent category if available
            if ($categoryName === 'Uncategorized' && $categoryId > 0 && !empty($course['category'])) {
                // Try to get parent category info
                try {
                    $category_info = call_moodle_api('core_course_get_categories', [
                        'criteria' => [
                            ['key' => 'ids', 'value' => $categoryId]
                        ]
                    ]);
                    
                    if (!empty($category_info[0]['name'])) {
                        $categoryName = $category_info[0]['name'];
                    }
                } catch (Exception $e) {
                    error_log("Error fetching category info for ID {$categoryId}: " . $e->getMessage());
                }
            }
            
            // Add course to result
            $result[] = [
                'id' => $course['id'] ?? 0,
                'fullname' => $course['fullname'] ?? 'Untitled Course',
                'shortname' => $course['shortname'] ?? '',
                'categoryid' => $categoryId,
                'categoryname' => $categoryName,
                'enrolledusercount' => $student_count,
                'teacher' => !empty($teachers) ? $teachers[0] : null,
                'teacher_count' => count($teachers),
                'visible' => $course['visible'] ?? 1
            ];
        }
        
        // Sort by enrolled users (descending)
        usort($result, function($a, $b) {
            return $b['enrolledusercount'] - $a['enrolledusercount'];
        });
        
        return $result;
        
    } catch (Exception $e) {
        log_error('Error in get_all_courses_with_enrollments: ' . $e->getMessage());
        return [];
    }
}

/**
 * Helper function to log errors
 */
function log_error($message, $data = null) {
    error_log("[ERROR] " . $message);
    if ($data !== null) {
        error_log("Data: " . print_r($data, true));
    }
}
?>
