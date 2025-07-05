<?php
require_once('config.php');

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Make a REST call to Moodle API
 */
function call_moodle_api($function_name, $params = array()) {
    try {
        // Build the API URL with required parameters
        $serverurl = rtrim(MOODLE_API_URL, '/') . '/webservice/rest/server.php';
        $query_params = [
            'wstoken' => MOODLE_API_TOKEN,
            'wsfunction' => $function_name,
            'moodlewsrestformat' => 'json'
        ];
        
        $serverurl .= '?' . http_build_query($query_params);
        
        // Log the request (without sensitive data)
        $log_url = str_replace(MOODLE_API_TOKEN, '***', $serverurl);
        error_log("\n=== Moodle API Request ===");
        error_log("Function: $function_name");
        error_log("URL: $log_url");
        error_log("Params: " . print_r($params, true));
        
        // Initialize cURL
        $ch = curl_init();
        
        // Set cURL options
        $options = [
            CURLOPT_URL => $serverurl,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => http_build_query($params, '', '&'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false, // Disable for testing, enable in production
            CURLOPT_SSL_VERIFYHOST => 0,     // Disable for testing, set to 2 in production
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
                'User-Agent: Moodle Analytics Dashboard/1.0'
            ],
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5
        ];
        
        curl_setopt_array($ch, $options);
        
        // Execute request
        $start_time = microtime(true);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $total_time = round((microtime(true) - $start_time) * 1000, 2);
        
        // Get error info
        $curl_error = curl_error($ch);
        $curl_errno = curl_errno($ch);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        
        // Close cURL resource
        curl_close($ch);
        
        // Log response info
        $log_response = is_string($response) && strlen($response) > 1000 
            ? substr($response, 0, 1000) . "... [truncated]" 
            : $response;
            
        error_log("\n=== Moodle API Response ===");
        error_log("HTTP Status: $http_code");
        error_log("Time: {$total_time}ms");
        error_log("Content-Type: $content_type");
        error_log("Response: " . $log_response);
        
        // Handle cURL errors
        if ($curl_errno) {
            $error_msg = "cURL Error [$curl_errno]: $curl_error";
            error_log($error_msg);
            return ['error' => $error_msg, 'curl_errno' => $curl_errno];
        }
        
        // Check for empty response
        if ($response === false || $response === '') {
            $error_msg = "Empty response from Moodle API";
            error_log($error_msg);
            return ['error' => $error_msg, 'http_code' => $http_code];
        }
        
        // Decode JSON response
        $decoded = json_decode($response, true);
        
        // Check for JSON decode errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error_msg = "JSON decode error: " . json_last_error_msg();
            error_log($error_msg);
            return [
                'error' => $error_msg,
                'json_error' => json_last_error_msg(),
                'http_code' => $http_code,
                'raw_response' => $log_response
            ];
        }
        
        // Check for Moodle exceptions or errors
        if (isset($decoded['exception'])) {
            $error_msg = "Moodle Exception: " . ($decoded['message'] ?? 'Unknown error');
            error_log($error_msg);
            error_log("Exception details: " . print_r($decoded, true));
            return [
                'error' => $error_msg,
                'exception' => $decoded['exception'],
                'errorcode' => $decoded['errorcode'] ?? null,
                'debuginfo' => $decoded['debuginfo'] ?? null,
                'http_code' => $http_code
            ];
        }
        
        // Check for Moodle error format
        if (isset($decoded['errorcode'])) {
            $error_msg = "Moodle Error [{$decoded['errorcode']}]: {$decoded['message']}";
            error_log($error_msg);
            return [
                'error' => $error_msg,
                'errorcode' => $decoded['errorcode'],
                'debuginfo' => $decoded['debuginfo'] ?? null,
                'http_code' => $http_code
            ];
        }
        
        return $decoded;
        
    } catch (Exception $e) {
        $error_msg = "Unexpected error in call_moodle_api: " . $e->getMessage();
        error_log($error_msg);
        error_log("Stack trace: " . $e->getTraceAsString());
        return [
            'error' => $error_msg,
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ];
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
        
        // Get total user count by fetching all users with pagination
        error_log("Getting total user count...");
        $stats['total_users'] = 0;
        $perpage = 1000;
        $page = 0;
        $has_more = true;
        
        while ($has_more) {
            $users = call_moodle_api('core_user_get_users', [
                'criteria[0][key]' => 'suspended',
                'criteria[0][value]' => 0, // Only active users
                'criteria[1][key]' => 'deleted',
                'criteria[1][value]' => 0, // Not deleted
                'limitfrom' => $page * $perpage,
                'limitnumber' => $perpage
            ]);
            
            if (isset($users['error'])) {
                error_log("Error getting users: " . $users['error']);
                $stats['total_users'] = 'Error: ' . $users['error'];
                break;
            }
            
            if (empty($users['users']) || !is_array($users['users'])) {
                $has_more = false;
            } else {
                $count = count($users['users']);
                $stats['total_users'] += $count;
                $page++;
                
                // If we got fewer users than requested, we've reached the end
                if ($count < $perpage) {
                    $has_more = false;
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
        
        // Get active users in last 60 minutes
        error_log("Getting active users...");
        $active_users = call_moodle_api('core_user_get_users', [
            'criteria[0][key]' => 'lastaccess',
            'criteria[0][value]' => time() - 3600, // Last hour
            'criteria[0][operator]' => '>',
            'criteria[1][key]' => 'suspended',
            'criteria[1][value]' => 0, // Only active users
            'criteria[2][key]' => 'deleted',
            'criteria[2][value]' => 0, // Not deleted
            'limitnumber' => 1 // We only need the count, not the actual users
        ]);
        
        if (isset($active_users['error'])) {
            error_log("Error getting active users: " . $active_users['error']);
            $stats['active_users'] = 'Error: ' . $active_users['error'];
        } else {
            // If we have a 'total' in the response, use that, otherwise count the users
            if (isset($active_users['total'])) {
                $stats['active_users'] = (int)$active_users['total'];
            } else {
                $stats['active_users'] = is_array($active_users['users'] ?? null) ? count($active_users['users']) : 0;
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
