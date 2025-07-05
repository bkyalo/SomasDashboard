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
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-6 shadow-lg transform transition-all hover:scale-105 hover:shadow-xl">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-100 text-sm font-medium">Total Users</p>
                                <h3 class="text-3xl font-bold text-white">
                                    <?php 
                                    if (is_numeric($stats['total_users'])) {
                                        echo number_format((float)$stats['total_users']);
                                    } else {
                                        echo htmlspecialchars($stats['total_users']);
                                    }
                                    ?>
                                </h3>
                            </div>
                            <div class="bg-blue-400 bg-opacity-20 p-3 rounded-full">
                                <i class="fas fa-users text-white text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <span class="text-orange-100 text-sm"><i class="fas fa-arrow-up mr-1"></i> 12% from last month</span>
                        </div>
                    </div>

                    <!-- Active Users Card -->
                    <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-6 shadow-lg transform transition-all hover:scale-105 hover:shadow-xl">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-100 text-sm font-medium">Active Users (1h)</p>
                                <h3 class="text-3xl font-bold text-white">
                                    <?php 
                                    if (is_numeric($stats['active_users'])) {
                                        echo number_format((float)$stats['active_users']);
                                    } else {
                                        echo htmlspecialchars($stats['active_users']);
                                    }
                                    ?>
                                </h3>
                            </div>
                            <div class="bg-green-400 bg-opacity-20 p-3 rounded-full">
                                <i class="fas fa-user-clock text-white text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <span class="text-green-100 text-sm"><i class="fas fa-bolt mr-1"></i> Currently active</span>
                        </div>
                    </div>

                    <!-- Total Courses Card -->
                    <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl p-6 shadow-lg transform transition-all hover:scale-105 hover:shadow-xl">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-purple-100 text-sm font-medium">Total Courses</p>
                                <h3 class="text-3xl font-bold text-white">
                                    <?php 
                                    if (is_numeric($stats['total_courses'])) {
                                        echo number_format((float)$stats['total_courses']);
                                    } else {
                                        echo htmlspecialchars($stats['total_courses']);
                                    }
                                    ?>
                                </h3>
                            </div>
                            <div class="bg-purple-400 bg-opacity-20 p-3 rounded-full">
                                <i class="fas fa-book text-white text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <span class="text-purple-100 text-sm"><i class="fas fa-chart-line mr-1"></i> 5 new this week</span>
                        </div>
                    </div>

                    <!-- Categories Card -->
                    <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl p-6 shadow-lg transform transition-all duration-300 hover:scale-[1.02] hover:shadow-xl hover:shadow-amber-500/40 group">
                        <div class="absolute inset-0 bg-gradient-to-br from-amber-400 to-orange-500 opacity-0 group-hover:opacity-30 rounded-xl transition-opacity duration-300"></div>
                        <div class="relative z-10">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-amber-100 text-sm font-medium mb-1">Categories</p>
                                    <h3 class="text-3xl font-bold text-white">
                                        <?php 
                                        if (is_numeric($stats['total_categories'])) {
                                            echo number_format((float)$stats['total_categories']);
                                        } else {
                                            echo htmlspecialchars($stats['total_categories']);
                                        }
                                        ?>
                                    </h3>
                                </div>
                                <div class="bg-amber-400 bg-opacity-30 p-3 rounded-full backdrop-blur-sm group-hover:bg-opacity-40 transition-all duration-300">
                                    <i class="fas fa-tags text-white text-xl group-hover:scale-110 transition-transform"></i>
                                </div>
                            </div>
                            <div class="mt-4 flex items-center">
                                <span class="text-amber-100 text-sm bg-amber-500/20 px-2 py-1 rounded-full">
                                    <i class="fas fa-layer-group mr-1"></i> Organized content
                                </span>
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

                <!-- Top Enrolled Courses Section -->
                <div class="bg-gradient-to-br from-gray-900 to-gray-800 rounded-2xl p-6 shadow-2xl mb-8 border border-gray-700 transform transition-all hover:shadow-purple-500/20 hover:border-purple-500/50">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-white flex items-center">
                            <span class="bg-clip-text text-transparent bg-gradient-to-r from-purple-400 to-pink-500">Top Enrolled Courses</span>
                            <span class="ml-3 px-3 py-1 bg-purple-900 bg-opacity-50 text-purple-300 text-xs font-semibold rounded-full">HOT</span>
                        </h2>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 top-courses-container">
                        <!-- Loading spinner will be shown initially -->
                        <div class="col-span-full text-center py-8">
                            <div class="inline-block animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-purple-500 mb-2"></div>
                            <p class="text-gray-400">Loading courses...</p>
                        </div>
                    </div>
                    
                    <div class="mt-6 text-center">
                        <a href="#" class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors">
                            View All Courses
                            <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="js/sidebar.js"></script>
    <script src="js/app.js"></script>
</body>
</html>
