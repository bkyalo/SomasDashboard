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

// Page title and active nav item
$pageTitle = 'Dashboard';
$activeNav = 'dashboard';

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
    $queries = [
        'total_users' => "SELECT COUNT(*) FROM mdl_user WHERE deleted = 0 AND id > 1",
        'total_courses' => "SELECT COUNT(*) FROM mdl_course WHERE id > 1",
        'active_users' => "SELECT COUNT(DISTINCT userid) FROM mdl_user_lastaccess WHERE timeaccess > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 DAY))",
        'total_enrollments' => "SELECT COUNT(*) FROM mdl_user_enrolments"
    ];
    
    foreach ($queries as $key => $query) {
        try {
            $stats[$key] = $pdo->query($query)->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error fetching $key: " . $e->getMessage());
            $stats[$key] = 'N/A';
        }
    }
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Somas Dashboard</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --sidebar-width: 250px;
            --topbar-height: 56px;
        }
        
        body {
            font-size: 0.9rem;
            background-color: #f8f9fa;
        }
        
        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: #2c3e50;
            color: #fff;
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .sidebar-brand {
            height: var(--topbar-height);
            display: flex;
            align-items: center;
            padding: 0 1rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: #fff;
            text-decoration: none;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar .nav-link {
            color: #b8c7ce;
            padding: 0.65rem 1rem;
            margin: 0.1rem 0.5rem;
            border-radius: 0.25rem;
            transition: all 0.2s;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }
        
        .sidebar .nav-link i {
            width: 1.5rem;
            text-align: center;
            margin-right: 0.5rem;
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 1.5rem;
            min-height: 100vh;
            transition: all 0.3s;
        }
        
        /* Topbar */
        .topbar {
            height: var(--topbar-height);
            background: #fff;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
            position: sticky;
            top: 0;
            z-index: 100;
            padding: 0 1.5rem;
        }
        
        /* Cards */
        .stat-card {
            border: none;
            border-radius: 0.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1) !important;
        }
        
        .stat-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                left: calc(-1 * var(--sidebar-width));
            }
            
            .sidebar.active {
                left: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
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
