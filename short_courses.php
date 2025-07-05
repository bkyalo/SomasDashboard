<?php
require_once 'config.php';
require_once 'api_functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Short Courses - Moodle Analytics Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        // Pass Moodle URL to JavaScript
        const MOODLE_URL = '<?php echo MOODLE_URL; ?>';
        const IS_SHORT_COURSES = true; // Flag to identify this is the short courses page
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
        <?php 
        // Set current page for active menu highlighting
        $current_page = 'short_courses.php';
        include 'includes/sidebar.php'; 
        ?>
        
        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">Short Courses (PDC)</h1>
                    <div class="flex items-center space-x-4">
                        <div class="flex space-x-2">
                            <div class="relative">
                                <input type="text" id="searchInput" placeholder="Search short courses..." 
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

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium">Total Short Courses</p>
                                <h3 class="text-2xl font-bold" id="totalCourses">0</h3>
                            </div>
                            <div class="bg-blue-400/20 p-3 rounded-lg">
                                <i class="fas fa-book text-xl"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium">Total Students</p>
                                <h3 class="text-2xl font-bold" id="totalStudents">0</h3>
                            </div>
                            <div class="bg-green-400/20 p-3 rounded-lg">
                                <i class="fas fa-users text-xl"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium">Total Teachers</p>
                                <h3 class="text-2xl font-bold" id="totalTeachers">0</h3>
                            </div>
                            <div class="bg-purple-400/20 p-3 rounded-lg">
                                <i class="fas fa-chalkboard-teacher text-xl"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-xl p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium">Categories</p>
                                <h3 class="text-2xl font-bold" id="totalCategories">0</h3>
                            </div>
                            <div class="bg-yellow-400/20 p-3 rounded-lg">
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
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teacher</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enrolled Students</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                        <div class="flex justify-center">
                                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                                        </div>
                                        <p class="mt-2">Loading short courses...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination will be inserted here by JavaScript -->
                    <div id="pagination" class="bg-white px-6 py-3 flex items-center justify-between border-t border-gray-200">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <a href="#" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Previous
                            </a>
                            <a href="#" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Next
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let allCategories = [];
        let currentPage = 1;
        let allCourses = [];
        
        // Debounce function to limit how often a function is called
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
        
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
                    console.log('Fetching initial short courses after loading categories...');
                    fetchCourses();
                } else {
                    const errorMsg = data.message || 'No data returned from categories API';
                    console.error('Failed to load categories:', errorMsg, data);
                    
                    // Show error to user
                    showNotification('warning', 'Could not load course categories. Showing all short courses.');
                    
                    // Still try to load courses even if categories fail
                    fetchCourses();
                }
            } catch (error) {
                console.error('Error fetching categories:', error);
                
                // Show error to user
                showNotification('error', 'Error loading course categories. Showing all short courses.');
                
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
            
            try {
                console.log('Populating category filter with categories:', allCategories);
                
                // Store current selected value
                const currentValue = categoryFilter.value;
                
                // Clear existing options except the first one
                while (categoryFilter.options.length > 1) {
                    categoryFilter.remove(1);
                }
                
                // Sort categories by name for better UX
                const sortedCategories = [...allCategories].sort((a, b) => {
                    return (a.name || '').localeCompare(b.name || '');
                });
                
                // Add categories to the dropdown
                sortedCategories.forEach(category => {
                    try {
                        // Ensure category has required fields
                        const categoryId = String(category.id ?? '');
                        const categoryName = String(category.name || 'Unnamed Category');
                        
                        if (categoryId && categoryName) {
                            const option = document.createElement('option');
                            option.value = categoryId;
                            option.textContent = categoryName;
                            categoryFilter.appendChild(option);
                            
                            // Log the added category for debugging
                            console.log('Added category:', { id: categoryId, name: categoryName });
                        } else {
                            console.warn('Skipping invalid category:', category);
                        }
                    } catch (err) {
                        console.error('Error processing category:', category, err);
                    }
                });
                
                // Restore selected value if it still exists
                if (currentValue) {
                    const optionExists = Array.from(categoryFilter.options).some(
                        opt => String(opt.value) === String(currentValue)
                    );
                    
                    if (optionExists) {
                        categoryFilter.value = currentValue;
                        console.log('Restored selected category:', currentValue);
                    } else {
                        console.log('Previous category selection not found in new options');
                    }
                }
                
                console.log('Category filter populated with', categoryFilter.options.length - 1, 'categories');
                
            } catch (error) {
                console.error('Error populating category filter:', error);
                // Show error in the UI if possible
                const errorElement = document.createElement('div');
                errorElement.className = 'text-red-500 text-sm mt-2';
                errorElement.textContent = 'Error loading categories';
                categoryFilter.parentNode.appendChild(errorElement);
            }
        }
        
        // Function to update the courses table with the provided courses
        function updateCoursesTable(courses) {
            const tbody = document.querySelector('#coursesTable tbody');
            if (!tbody) return;
            
            if (!courses || courses.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            No short courses found.
                        </td>
                    </tr>`;
                return;
            }
            
            // Clear existing rows
            tbody.innerHTML = '';
            
            // Add each course as a row
            courses.forEach(course => {
                const row = document.createElement('tr');
                row.className = 'hover:bg-gray-50';
                
                // Format the course name with a link to view details
                const courseName = document.createElement('td');
                courseName.className = 'px-6 py-4 whitespace-nowrap';
                courseName.innerHTML = `
                    <div class="flex items-center">
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">
                                <a href="${MOODLE_URL}/course/view.php?id=${course.id}" target="_blank" class="text-blue-600 hover:text-blue-800 hover:underline">
                                    ${course.fullname || 'Untitled Course'}
                                </a>
                            </div>
                            <div class="text-sm text-gray-500">${course.shortname || ''}</div>
                        </div>
                    </div>`;
                
                // Category
                const category = document.createElement('td');
                category.className = 'px-6 py-4 whitespace-nowrap';
                category.innerHTML = `
                    <div class="text-sm text-gray-900">${course.categoryname || 'Uncategorized'}</div>`;
                
                // Teacher
                const teacher = document.createElement('td');
                teacher.className = 'px-6 py-4 whitespace-nowrap';
                teacher.innerHTML = `
                    <div class="text-sm text-gray-900">${course.teacher || 'N/A'}</div>`;
                
                // Enrolled students
                const students = document.createElement('td');
                students.className = 'px-6 py-4 whitespace-nowrap';
                students.innerHTML = `
                    <div class="text-sm text-gray-900">${course.enrolledusercount || 0}</div>`;
                
                // Actions
                const actions = document.createElement('td');
                actions.className = 'px-6 py-4 whitespace-nowrap text-right text-sm font-medium';
                actions.innerHTML = `
                    <a href="${MOODLE_URL}/course/view.php?id=${course.id}" target="_blank" class="text-blue-600 hover:text-blue-900 mr-4">
                        <i class="fas fa-external-link-alt"></i> View
                    </a>
                    <a href="#" class="text-indigo-600 hover:text-indigo-900" onclick="showCourseDetails(${course.id}); return false;">
                        <i class="fas fa-chart-line"></i> Analytics
                    </a>`;
                
                // Append all cells to the row
                row.appendChild(courseName);
                row.appendChild(category);
                row.appendChild(teacher);
                row.appendChild(students);
                row.appendChild(actions);
                
                // Add the row to the table
                tbody.appendChild(row);
            });
        }
        
        // Function to update stats
        async function updateStats() {
            try {
                const response = await fetch('api/get_stats.php');
                const data = await response.json();
                
                if (data.success) {
                    // Update the stats cards
                    document.getElementById('totalCourses').textContent = data.data.total_short_courses || 0;
                    document.getElementById('totalStudents').textContent = data.data.total_students || 0;
                    document.getElementById('totalTeachers').textContent = data.data.total_teachers || 0;
                    document.getElementById('totalCategories').textContent = data.data.total_categories || 0;
                }
            } catch (error) {
                console.error('Error updating stats:', error);
            }
        }
        
        // Function to fetch courses from the API
        async function fetchCourses() {
            try {
                const searchQuery = document.getElementById('searchInput').value;
                const categoryFilter = document.getElementById('categoryFilter');
                const categoryValue = categoryFilter ? categoryFilter.value : '';
                const rowsPerPage = document.getElementById('rowsPerPage').value;
                
                // Debug log
                console.log('Fetching short courses with params:', {
                    search: searchQuery,
                    category: categoryValue,
                    page: currentPage,
                    per_page: rowsPerPage,
                    shortname: 'PDC-' // Filter for PDC courses
                });
                
                // Show loading state
                const tbody = document.querySelector('#coursesTable tbody');
                if (tbody) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex justify-center">
                                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                                </div>
                                <p class="mt-2">Loading short courses...</p>
                            </td>
                        </tr>`;
                }
                
                // Build query parameters
                const params = new URLSearchParams({
                    page: currentPage,
                    per_page: rowsPerPage,
                    search: searchQuery,
                    shortname: 'PDC-' // Filter for PDC courses
                });
                
                if (categoryValue) {
                    params.append('category', categoryValue);
                    console.log('Added category filter:', categoryValue);
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
                } else {
                    throw new Error(data.error || 'Failed to fetch short courses');
                }
            } catch (error) {
                console.error('Error fetching short courses:', error);
                const tbody = document.querySelector('#coursesTable tbody');
                if (tbody) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-red-500">
                                Error loading short courses. Please try again.
                            </td>
                        </tr>`;
                }
            }
        }
        
        // Function to render pagination
        function renderPagination(pagination) {
            const paginationContainer = document.getElementById('pagination');
            if (!paginationContainer) return;
            
            const { total, per_page, current_page, last_page } = pagination;
            const totalPages = Math.ceil(total / per_page);
            
            if (totalPages <= 1) {
                paginationContainer.innerHTML = '';
                return;
            }
            
            let paginationHTML = `
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium">${(current_page - 1) * per_page + 1}</span>
                            to <span class="font-medium">${Math.min(current_page * per_page, total)}</span>
                            of <span class="font-medium">${total}</span> results
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">`;
            
            // Previous button
            paginationHTML += `
                <a href="#" onclick="changePage(${current_page > 1 ? current_page - 1 : 1}); return false;" 
                   class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 ${current_page === 1 ? 'opacity-50 cursor-not-allowed' : ''}">
                    <span class="sr-only">Previous</span>
                    <i class="fas fa-chevron-left h-5 w-5"></i>
                </a>`;
            
            // Page numbers
            const maxPagesToShow = 5;
            let startPage = Math.max(1, current_page - Math.floor(maxPagesToShow / 2));
            let endPage = Math.min(last_page, startPage + maxPagesToShow - 1);
            
            if (endPage - startPage + 1 < maxPagesToShow) {
                startPage = Math.max(1, endPage - maxPagesToShow + 1);
            }
            
            if (startPage > 1) {
                paginationHTML += `
                    <a href="#" onclick="changePage(1); return false;" 
                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                        1
                    </a>`;
                if (startPage > 2) {
                    paginationHTML += `
                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                            ...
                        </span>`;
                }
            }
            
            for (let i = startPage; i <= endPage; i++) {
                paginationHTML += `
                    <a href="#" onclick="changePage(${i}); return false;" 
                       class="relative inline-flex items-center px-4 py-2 border ${i === current_page ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'} text-sm font-medium">
                        ${i}
                    </a>`;
            }
            
            if (endPage < last_page) {
                if (endPage < last_page - 1) {
                    paginationHTML += `
                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                            ...
                        </span>`;
                }
                paginationHTML += `
                    <a href="#" onclick="changePage(${last_page}); return false;" 
                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                        ${last_page}
                    </a>`;
            }
            
            // Next button
            paginationHTML += `
                <a href="#" onclick="changePage(${current_page < last_page ? current_page + 1 : last_page}); return false;" 
                   class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 ${current_page === last_page ? 'opacity-50 cursor-not-allowed' : ''}">
                    <span class="sr-only">Next</span>
                    <i class="fas fa-chevron-right h-5 w-5"></i>
                </a>`;
            
            paginationHTML += `
                        </nav>
                    </div>
                </div>`;
            
            paginationContainer.innerHTML = paginationHTML;
        }
        
        // Function to handle page changes
        function changePage(page) {
            if (page < 1) page = 1;
            currentPage = page;
            fetchCourses();
            // Scroll to top of the table
            const table = document.querySelector('#coursesTable');
            if (table) {
                table.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
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
                categoryFilter.addEventListener('change', function() {
                    currentPage = 1; // Reset to first page when changing categories
                    fetchCourses();
                });
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
            
            console.log('Short Courses page initialized');
        });
        
        // Make the changePage function available globally for pagination
        window.changePage = changePage;
        
        // Function to show course details (placeholder)
        function showCourseDetails(courseId) {
            alert(`Course details for ID: ${courseId}\nThis feature will be implemented soon.`);
        }
    </script>
</body>
</html>