<?php
require_once 'config.php';
require_once 'api_functions.php';

// Get site statistics
$stats = get_site_statistics();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moodle Analytics Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="bg-blue-800 text-white w-64 px-4 py-6">
            <div class="flex items-center justify-center mb-8">
                <i class="fas fa-graduation-cap text-3xl mr-2"></i>
                <h1 class="text-2xl font-bold">Moodle Analytics</h1>
            </div>
            <nav>
                <a href="#" class="flex items-center px-4 py-3 text-white bg-blue-900 rounded-lg mb-2">
                    <i class="fas fa-tachometer-alt mr-3"></i>
                    Dashboard
                </a>
                <a href="#" class="flex items-center px-4 py-3 text-blue-200 hover:bg-blue-700 rounded-lg mb-2">
                    <i class="fas fa-users mr-3"></i>
                    Users
                </a>
                <a href="#" class="flex items-center px-4 py-3 text-blue-200 hover:bg-blue-700 rounded-lg mb-2">
                    <i class="fas fa-book mr-3"></i>
                    Courses
                </a>
                <a href="#" class="flex items-center px-4 py-3 text-blue-200 hover:bg-blue-700 rounded-lg mb-2">
                    <i class="fas fa-chart-bar mr-3"></i>
                    Reports
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto">
            <!-- Top Navigation -->
            <header class="bg-white shadow-sm">
                <div class="flex justify-between items-center px-6 py-4">
                    <h2 class="text-xl font-semibold text-gray-800">Dashboard</h2>
                    <div class="flex items-center">
                        <div class="relative">
                            <input type="text" class="pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Search...">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                        <div class="ml-4 relative">
                            <button class="flex items-center focus:outline-none">
                                <img src="https://ui-avatars.com/api/?name=Admin&background=4f46e5&color=fff" alt="User" class="w-8 h-8 rounded-full">
                                <span class="ml-2 text-sm font-medium">Admin</span>
                                <i class="fas fa-chevron-down ml-1 text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <main class="p-6">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Users Card -->
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                                    <i class="fas fa-users text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-gray-500 text-sm">Total Users</p>
                                    <div class="stat-value">
                                        <?php 
                                        if (is_numeric($stats['total_users'])) {
                                            echo number_format((float)$stats['total_users']);
                                        } else {
                                            echo htmlspecialchars($stats['total_users']);
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Courses Card -->
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                                    <i class="fas fa-book text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-gray-500 text-sm">Total Courses</p>
                                    <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['total_courses'] ?? 0); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Categories Card -->
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                                    <i class="fas fa-folder text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-gray-500 text-sm">Categories</p>
                                    <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['total_categories'] ?? 0); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Active Users Card -->
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                                    <i class="fas fa-user-clock text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-gray-500 text-sm">Active Users (60m)</p>
                                    <div class="stat-value">
                                        <?php 
                                        if (is_numeric($stats['active_users'])) {
                                            echo number_format((float)$stats['active_users']);
                                        } else {
                                            echo htmlspecialchars($stats['active_users']);
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Activity Chart -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-800">User Activity</h3>
                            <div class="flex space-x-2">
                                <button class="px-3 py-1 text-sm bg-blue-100 text-blue-600 rounded-lg">Day</button>
                                <button class="px-3 py-1 text-sm text-gray-500 hover:bg-gray-100 rounded-lg">Week</button>
                                <button class="px-3 py-1 text-sm text-gray-500 hover:bg-gray-100 rounded-lg">Month</button>
                            </div>
                        </div>
                        <div class="h-64 flex items-center justify-center bg-gray-50 rounded-lg">
                            <p class="text-gray-400">Activity chart will be displayed here</p>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Activity</h3>
                        <div class="space-y-4">
                            <div class="flex items-start">
                                <div class="p-2 bg-blue-100 text-blue-600 rounded-lg mr-3">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium">New user registered</p>
                                    <p class="text-xs text-gray-500">2 minutes ago</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <div class="p-2 bg-green-100 text-green-600 rounded-lg mr-3">
                                    <i class="fas fa-book"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium">New course added: Advanced PHP</p>
                                    <p class="text-xs text-gray-500">1 hour ago</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <div class="p-2 bg-purple-100 text-purple-600 rounded-lg mr-3">
                                    <i class="fas fa-comment"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium">New forum post in Web Development</p>
                                    <p class="text-xs text-gray-500">3 hours ago</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="js/sidebar.js"></script>
    <script src="js/app.js"></script>
</body>
</html>
