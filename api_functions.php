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
    curl_setopt($ch, CURLOPT_URL, MOODLE_API_URL);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
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
    $error = curl_error($ch);
    $errno = curl_errno($ch);
    
    // Log the response
    error_log("\n=== Moodle API Response ===");
    error_log("Status: $http_code");
    error_log("Response: " . substr($response, 0, 2000));
    
    // Check for cURL errors
    if ($errno) {
        $error_message = "cURL Error ($errno): $error";
        error_log("ERROR: $error_message");
        curl_close($ch);
        return ['error' => $error_message];
    }
    
    curl_close($ch);
    
    // Check for empty response
    if (empty($response)) {
        $error_message = "Empty response from Moodle API";
        error_log("ERROR: $error_message");
        return ['error' => $error_message];
    }
    
    // Parse the JSON response
    $result = json_decode($response, true);
    
    // Check for JSON parsing errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        $error_message = 'JSON Parse Error: ' . json_last_error_msg() . ' - Response: ' . substr($response, 0, 500);
        error_log("ERROR: $error_message");
        return ['error' => $error_message];
    }
    
    // Check for Moodle exceptions or errors
    if (isset($result['exception'])) {
        $error_message = "Moodle Exception: " . ($result['message'] ?? 'Unknown error');
        error_log("ERROR: $error_message");
        if (isset($result['debuginfo'])) {
            error_log("Debug Info: " . $result['debuginfo']);
        }
        return ['error' => $error_message];
    }
    
    // Check for error message in the response
    if (isset($result['error'])) {
        $error_message = "Moodle Error: " . $result['error'];
        error_log("ERROR: $error_message");
        return ['error' => $error_message];
    }
    
    error_log("API Call Successful");
    return $result;
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

// Helper function to log errors
function log_error($message, $data = null) {
    error_log("[ERROR] $message");
    if ($data !== null) {
        error_log("[ERROR DATA] " . print_r($data, true));
    }
}
?>
