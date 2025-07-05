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
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">All Courses</h1>
                    <div class="flex items-center space-x-4">
                        <div class="flex space-x-2">
                            <div class="relative">
                                <input type="text" id="searchInput" placeholder="Search courses..." 
                                    class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm w-64">
                                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                            </div>
                            <div class="relative">
                                <select id="categoryFilter" class="pl-3 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm w-48 appearance-none bg-white">
                                    <option value="">All Categories</option>
                                    <!-- Categories will be populated by JavaScript -->
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                    <i class="fas fa-chevron-down text-xs"></i>
                                </div>
                            </div>
                        </div>
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
        // Global variables
        let allCategories = [];
        let currentPage = 1;
        let totalPages = 1;
        
        // Function to fetch categories from the API
        async function fetchCategories() {
            console.log('Starting to fetch categories...');
            
            try {
                const response = await fetch('api/get_categories.php');
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Received categories response:', data);
                
                if (data.success && Array.isArray(data.data)) {
                    console.log(`Successfully loaded ${data.data.length} categories`);
                    allCategories = data.data;
                    
                    // Log first few categories for verification
                    if (allCategories.length > 0) {
                        console.log('Sample categories:', allCategories.slice(0, 3));
                    }
                    
                    populateCategoryFilter();
                    
                    // Trigger initial course load after categories are loaded
                    console.log('Fetching initial courses after loading categories...');
                    fetchCourses();
                } else {
                    const errorMsg = data.message || 'No data returned from categories API';
                    console.error('Failed to load categories:', errorMsg, data);
                    
                    // Show error to user
                    showNotification('warning', 'Could not load course categories. Showing all courses.');
                    
                    // Still try to load courses even if categories fail
                    fetchCourses();
                }
            } catch (error) {
                console.error('Error fetching categories:', error);
                
                // Show error to user
                showNotification('error', 'Error loading course categories. Showing all courses.');
                
                // Still try to load courses even if categories fail
                fetchCourses();
            }
        }
        
        // Helper function to show notifications
        function showNotification(type, message) {
            // Check if notification container exists, if not create it
            let container = document.getElementById('notification-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'notification-container';
                container.className = 'fixed top-4 right-4 z-50 space-y-2 w-80';
                document.body.appendChild(container);
            }
            
            const notification = document.createElement('div');
            const bgColor = type === 'error' ? 'bg-red-100 border-red-500 text-red-700' : 
                           type === 'success' ? 'bg-green-100 border-green-500 text-green-700' :
                           'bg-yellow-100 border-yellow-500 text-yellow-700';
            
            notification.className = `border-l-4 p-4 ${bgColor} rounded shadow-lg`;
            notification.role = 'alert';
            notification.innerHTML = `
                <p class="font-bold">${type === 'error' ? 'Error' : type === 'success' ? 'Success' : 'Notice'}</p>
                <p>${message}</p>
            `;
            
            container.appendChild(notification);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transition = 'opacity 0.5s';
                setTimeout(() => notification.remove(), 500);
            }, 5000);
        }
        
        // Function to populate the category filter dropdown
        function populateCategoryFilter() {
            const categoryFilter = document.getElementById('categoryFilter');
            if (!categoryFilter) {
                console.error('Category filter element not found in the DOM');
                return;
            }
            
            // Clear existing options except the first one (All Categories)
            while (categoryFilter.options.length > 1) {
                categoryFilter.remove(1);
            }
            
            // Add categories to the dropdown
            allCategories.forEach(category => {
                const option = document.createElement('option');
                option.value = category.id;
                option.textContent = category.name;
                categoryFilter.appendChild(option);
            });
            
            // Add event listener for category filter change
            categoryFilter.removeEventListener('change', handleCategoryChange); // Remove existing listener to avoid duplicates
            categoryFilter.addEventListener('change', handleCategoryChange);
        }
        
        // Function to handle category filter change
        function handleCategoryChange(e) {
            currentPage = 1; // Reset to first page when changing categories
            fetchCourses();
        }
        
        // Function to update stats from the API
        async function updateStats() {
            try {
                const response = await fetch('api/get_stats.php');
                const data = await response.json();
                
                if (data.success && data.data) {
                    // Update the stats cards
                    if (data.data.total_courses !== undefined) {
                        document.getElementById('totalCourses').textContent = data.data.total_courses;
                    }
                    if (data.data.active_users !== undefined) {
                        document.getElementById('totalStudents').textContent = data.data.active_users;
                    }
                    if (data.data.total_teachers !== undefined) {
                        document.getElementById('totalTeachers').textContent = data.data.total_teachers;
                    } else if (data.data.total_users !== undefined) {
                        // Fallback: Estimate teachers as 10% of total users if not provided
                        const estimatedTeachers = Math.max(1, Math.floor(data.data.total_users * 0.1));
                        document.getElementById('totalTeachers').textContent = estimatedTeachers;
                    }
                    if (data.data.total_categories !== undefined) {
                        document.getElementById('totalCategories').textContent = data.data.total_categories;
                    }
                }
            } catch (error) {
                console.error('Error updating stats:', error);
            }
        }

        // Function to update the courses table with optional filtering
        function updateCoursesTable(courses) {
            const tbody = document.querySelector('#coursesTable tbody');
            if (!tbody) return;
            
            if (!courses || courses.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center py-8 text-gray-500">
                            No courses found matching your criteria
                        </td>
                    </tr>`;
                return;
            }
            
            tbody.innerHTML = courses.map(course => `
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-book text-blue-600"></i>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">${escapeHtml(course.fullname)}</div>
                                <div class="text-xs text-gray-500">${escapeHtml(course.categoryname || 'Uncategorized')}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        ${escapeHtml(course.shortname)}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        ${course.teacher ? escapeHtml(course.teacher) : 'No teacher assigned'}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2.5 py-0.5 inline-flex text-xs font-medium rounded-full ${getEnrollmentBadgeClass(course.enrolledusercount)}">
                            ${course.enrolledusercount} enrolled
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="${MOODLE_URL}/course/view.php?id=${course.id}" target="_blank" class="text-blue-600 hover:text-blue-900">
                            View <i class="fas fa-external-link-alt ml-1 text-xs"></i>
                        </a>
                    </td>
                </tr>`).join('');
        }
        
        // Function to fetch courses with filters
        async function fetchCourses() {
            try {
                const searchQuery = document.getElementById('searchInput').value;
                const categoryFilter = document.getElementById('categoryFilter');
                const categoryValue = categoryFilter ? categoryFilter.value : '';
                const rowsPerPage = document.getElementById('rowsPerPage').value;
                
                // Show loading state
                const tbody = document.querySelector('#coursesTable tbody');
                if (tbody) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="5" class="text-center py-8">
                                <div class="flex justify-center">
                                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                                </div>
                                <p class="mt-2 text-gray-600">Loading courses...</p>
                            </td>
                        </tr>`;
                }
                
                // Build query parameters
                const params = new URLSearchParams({
                    page: currentPage,
                    per_page: rowsPerPage,
                    search: searchQuery
                });
                
                if (categoryValue) {
                    params.append('category', categoryValue);
                }
                
                const response = await fetch(`api/get_courses.php?${params.toString()}`);
                const data = await response.json();
                
                if (data.success) {
                    allCourses = data.data || [];
                    updateCoursesTable(allCourses);
                    
                    // Update pagination
                    if (data.pagination) {
                        renderPagination(data.pagination);
                    }
                    
                    // Update stats
                    updateStats();
                } else {
                    throw new Error(data.error || 'Failed to fetch courses');
                }
            } catch (error) {
                console.error('Error fetching courses:', error);
                const tbody = document.querySelector('#coursesTable tbody');
                if (tbody) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="5" class="text-center py-8 text-red-500">
                                Error loading courses. Please try again.
                                <br><small>${error.message || ''}</small>
                            </td>
                        </tr>`;
                }
            }
        }
        
        // Function to handle category filter change
        function handleCategoryChange(e) {
            currentPage = 1; // Reset to first page when changing categories
            fetchCourses();
        }

        // Initialize stats when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            updateStats();
            fetchCategories();
            
            // Set up event listeners
            const searchInput = document.getElementById('searchInput');
            const categoryFilter = document.getElementById('categoryFilter');
            const rowsPerPage = document.getElementById('rowsPerPage');
            const refreshButton = document.getElementById('refreshButton');
            
            // Search input with debounce
            if (searchInput) {
                searchInput.addEventListener('input', debounce(fetchCourses, 300));
            }
            
            // Category filter
            if (categoryFilter) {
                console.log('Category filter element found, adding event listener');
                categoryFilter.addEventListener('change', handleCategoryChange);
            } else {
                console.error('Category filter element not found!');
            }
            
            // Rows per page
            if (rowsPerPage) {
                rowsPerPage.addEventListener('change', function() {
                    currentPage = 1;
                    fetchCourses();
                });
            }
            
            // Refresh button
            if (refreshButton) {
                refreshButton.addEventListener('click', function() {
                    currentPage = 1;
                    if (searchInput) searchInput.value = '';
                    if (categoryFilter) categoryFilter.value = '';
                    fetchCourses();
                });
            }
            
            // Update stats every 5 minutes
            setInterval(updateStats, 5 * 60 * 1000);
            
            console.log('Event listeners initialized');
        });
    </script>
    <script>
        // Utility function to escape HTML to prevent XSS
        function escapeHtml(unsafe) {
            return unsafe
                .toString()
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Function to get the appropriate badge class based on enrollment count
        function getEnrollmentBadgeClass(count) {
            if (count === 0) return 'bg-gray-100 text-gray-800';
            if (count < 20) return 'bg-blue-100 text-blue-800';
            if (count < 50) return 'bg-green-100 text-green-800';
            if (count < 100) return 'bg-yellow-100 text-yellow-800';
            return 'bg-red-100 text-red-800';
        }

        // Function to render pagination controls
        function renderPagination(pagination) {
            const paginationContainer = document.getElementById('pagination');
            if (!paginationContainer) return;
            
            const { current_page, total_pages } = pagination;
            totalPages = total_pages;
            
            let paginationHTML = '<div class="flex items-center space-x-1">';
            
            // Previous button
            paginationHTML += `
                <button 
                    onclick="changePage(${current_page - 1})" 
                    ${current_page === 1 ? 'disabled' : ''}
                    class="px-3 py-1 rounded-md ${current_page === 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-50'}"
                >
                    &larr; Previous
                </button>`;
            
            // Page numbers
            const maxVisiblePages = 5;
            let startPage = Math.max(1, current_page - Math.floor(maxVisiblePages / 2));
            let endPage = Math.min(total_pages, startPage + maxVisiblePages - 1);
            
            if (endPage - startPage + 1 < maxVisiblePages) {
                startPage = Math.max(1, endPage - maxVisiblePages + 1);
            }
            
            if (startPage > 1) {
                paginationHTML += `
                    <button onclick="changePage(1)" class="px-3 py-1 rounded-md bg-white text-gray-700 hover:bg-gray-50">
                        1
                    </button>`;
                if (startPage > 2) {
                    paginationHTML += '<span class="px-2">...</span>';
                }
            }
            
            for (let i = startPage; i <= endPage; i++) {
                paginationHTML += `
                    <button 
                        onclick="changePage(${i})" 
                        class="w-10 h-10 rounded-md ${i === current_page ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'}"
                    >
                        ${i}
                    </button>`;
            }
            
            if (endPage < total_pages) {
                if (endPage < total_pages - 1) {
                    paginationHTML += '<span class="px-2">...</span>';
                }
                paginationHTML += `
                    <button onclick="changePage(${total_pages})" class="px-3 py-1 rounded-md bg-white text-gray-700 hover:bg-gray-50">
                        ${total_pages}
                    </button>`;
            }
            
            // Next button
            paginationHTML += `
                <button 
                    onclick="changePage(${current_page + 1})" 
                    ${current_page === total_pages ? 'disabled' : ''}
                    class="px-3 py-1 rounded-md ${current_page === total_pages ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-50'}"
                >
                    Next &rarr;
                </button>`;
            
            paginationHTML += '</div>';
            
            paginationContainer.innerHTML = paginationHTML;
        }

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
