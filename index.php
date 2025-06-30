<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load configuration
$configFile = __DIR__ . '/config/db_config.php';

if (!file_exists($configFile)) {
    die("Error: Database configuration file not found at: " . $configFile);
}

// Load the configuration
$config = require $configFile;

// Initialize variables
$error = null;
$stats = [
    'total_users' => 0,
    'total_courses' => 0,
    'active_users' => 0,
    'total_enrollments' => 0
];

try {
    // Include the database connection class
    require_once __DIR__ . '/MoodleDBConnection.php';
    
    // Initialize database connection using config file
    $db = new MoodleDBConnection(
        $config['host'],
        $config['dbname'],
        $config['username'],
        $config['password']
    );
    
    // Get the PDO connection
    $pdo = $db->connect();
    
    // Get basic statistics with error handling for each query
    try {
        $stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM mdl_user WHERE deleted = 0 AND id > 1")->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error fetching total users: " . $e->getMessage());
        $stats['total_users'] = 'N/A';
    }
    
    try {
        $stats['total_courses'] = $pdo->query("SELECT COUNT(*) FROM mdl_course WHERE id > 1")->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error fetching total courses: " . $e->getMessage());
        $stats['total_courses'] = 'N/A';
    }
    
    try {
        $stats['active_users'] = $pdo->query("SELECT COUNT(DISTINCT userid) FROM mdl_user_lastaccess WHERE timeaccess > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 DAY))")->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error fetching active users: " . $e->getMessage());
        $stats['active_users'] = 'N/A';
    }
    
    try {
        $stats['total_enrollments'] = $pdo->query("SELECT COUNT(*) FROM mdl_user_enrolments")->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error fetching total enrollments: " . $e->getMessage());
        $stats['total_enrollments'] = 'N/A';
    }
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moodle Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .stat-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex justify-between items-center">
                    <h1 class="text-3xl font-bold text-gray-900">Moodle Dashboard</h1>
                    <div class="text-sm text-gray-500">
                        Last updated: <?php echo date('F j, Y, g:i a'); ?>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <strong class="font-bold">Error!</strong>
                    <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <?php
                $statCards = [
                    [
                        'title' => 'Total Users',
                        'value' => $stats['total_users'],
                        'icon' => 'users',
                        'color' => 'bg-blue-500'
                    ],
                    [
                        'title' => 'Total Courses',
                        'value' => $stats['total_courses'],
                        'icon' => 'book',
                        'color' => 'bg-green-500'
                    ],
                    [
                        'title' => 'Active Users (30d)',
                        'value' => $stats['active_users'],
                        'icon' => 'user-check',
                        'color' => 'bg-yellow-500'
                    ],
                    [
                        'title' => 'Total Enrollments',
                        'value' => $stats['total_enrollments'],
                        'icon' => 'user-graduate',
                        'color' => 'bg-purple-500'
                    ]
                ];

                foreach ($statCards as $card): ?>
                    <div class="stat-card bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full <?php echo $card['color']; ?> text-white mr-4">
                                <i class="fas fa-<?php echo $card['icon']; ?> text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm font-medium"><?php echo $card['title']; ?></p>
                                <p class="text-2xl font-bold"><?php echo number_format($card['value']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Recent Activity Section -->
            <div class="bg-white shadow rounded-lg p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">Recent Activity</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            try {
                                $query = "SELECT l.id, u.firstname, u.lastname, l.action, l.timecreated, c.fullname as coursename 
                                         FROM mdl_logstore_standard_log l 
                                         JOIN mdl_user u ON l.userid = u.id 
                                         LEFT JOIN mdl_course c ON l.courseid = c.id 
                                         WHERE l.action IN ('viewed', 'created', 'updated')
                                         ORDER BY l.timecreated DESC 
                                         LIMIT 10";
                                $stmt = $pdo->query($query);
                                
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): 
                                    $timeAgo = time() - $row['timecreated'];
                                    $timeText = '';
                                    
                                    if ($timeAgo < 60) {
                                        $timeText = 'Just now';
                                    } elseif ($timeAgo < 3600) {
                                        $mins = floor($timeAgo / 60);
                                        $timeText = $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
                                    } else {
                                        $timeText = date('M j, Y g:i A', $row['timecreated']);
                                    }
                            ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-gray-200 rounded-full flex items-center justify-center">
                                                <i class="fas fa-user text-gray-500"></i>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php 
                                                $actionClass = 'bg-gray-100 text-gray-800';
                                                if ($row['action'] === 'viewed') $actionClass = 'bg-blue-100 text-blue-800';
                                                elseif ($row['action'] === 'created') $actionClass = 'bg-green-100 text-green-800';
                                                elseif ($row['action'] === 'updated') $actionClass = 'bg-yellow-100 text-yellow-800';
                                                echo $actionClass;
                                            ?>">
                                            <?php echo ucfirst(htmlspecialchars($row['action'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo !empty($row['coursename']) ? htmlspecialchars($row['coursename']) : 'System'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $timeText; ?>
                                    </td>
                                </tr>
                            <?php 
                                endwhile; 
                            } catch (Exception $e) {
                                // Silently handle any errors in the recent activity section
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <footer class="bg-white border-t border-gray-200">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <p class="text-center text-sm text-gray-500">
                &copy; <?php echo date('Y'); ?> Moodle Dashboard. All rights reserved.
            </p>
        </div>
    </footer>
</body>
</html>
