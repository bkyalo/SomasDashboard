<?php
require_once 'config.php';
require_once 'api_functions.php';

// We'll load courses via AJAX for better performance
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses - Moodle Analytics Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        // Pass Moodle URL to JavaScript
        const MOODLE_URL = '<?php echo MOODLE_URL; ?>';
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .course-card {
            transition: all 0.3s ease;
        }
        .course-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto">
            <!-- Top Navigation -->
            <header class="bg-white shadow-sm">
                <div class="flex justify-between items-center px-6 py-4">
                    <h2 class="text-xl font-semibold text-gray-800">All Courses</h2>
                    <div class="flex items-center space-x-4">
                        <div class="relative flex-1 max-w-md">
                            <input type="text" id="searchInput" placeholder="Search by course name, code, or teacher..." 
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                        <div class="flex items-center space-x-2">
                            <select id="rowsPerPage" class="text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="10">10 per page</option>
                                <option value="25">25 per page</option>
                                <option value="50">50 per page</option>
                                <option value="100">100 per page</option>
                            </select>
                            <button id="refreshButton" class="p-2 text-gray-500 hover:text-blue-600 rounded-full hover:bg-gray-100">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="p-6">
                <!-- Stats and Search -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-100 text-sm font-medium">Total Courses</p>
                                <h3 class="text-3xl font-bold" id="totalCourses">0</h3>
                            </div>
                            <div class="bg-blue-400 bg-opacity-30 p-3 rounded-full">
                                <i class="fas fa-book text-xl"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-100 text-sm font-medium">Active Students</p>
                                <h3 class="text-3xl font-bold" id="totalStudents">0</h3>
                            </div>
                            <div class="bg-green-400 bg-opacity-30 p-3 rounded-full">
                                <i class="fas fa-users text-xl"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-purple-100 text-sm font-medium">Teachers</p>
                                <h3 class="text-3xl font-bold" id="totalTeachers">0</h3>
                            </div>
                            <div class="bg-purple-400 bg-opacity-30 p-3 rounded-full">
                                <i class="fas fa-chalkboard-teacher text-xl"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-r from-amber-500 to-amber-600 rounded-xl p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-amber-100 text-sm font-medium">Categories</p>
                                <h3 class="text-3xl font-bold" id="totalCategories">0</h3>
                            </div>
                            <div class="bg-amber-400 bg-opacity-30 p-3 rounded-full">
                                <i class="fas fa-tags text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Courses Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" id="coursesTable">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course Name</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teacher</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Students</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="coursesTableBody" class="bg-white divide-y divide-gray-200">
                                <!-- Rows will be populated by JavaScript -->
                                <tr id="loadingIndicator">
                                    <td colspan="5" class="px-6 py-8 text-center">
                                        <div class="flex justify-center">
                                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                                        </div>
                                        <p class="mt-2 text-sm text-gray-500">Loading courses...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <!-- No courses message (hidden by default) -->
                        <div id="noCoursesMessage" class="hidden px-6 py-8 text-center">
                            <i class="fas fa-book-open text-4xl text-gray-300 mb-2"></i>
                            <p class="text-gray-500">No courses found. Try adjusting your search.</p>
                        </div>
                    </div>
                    
                    <!-- Pagination -->
                    <div id="pagination" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                        <!-- Pagination will be inserted here by JavaScript -->
                    </div>
                    

                </div>
            </main>
        </div>
    </div>

    <!-- Include JavaScript -->
    <script src="js/courses.js"></script>
    <script>
        // Make the changePage function available globally for pagination
        // The actual implementation is in courses.js
        window.changePage = function(page) {
            if (typeof changePage === 'function') {
                changePage(page);
            }
        };
    </script>
</body>
</html>
