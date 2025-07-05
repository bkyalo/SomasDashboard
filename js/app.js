/**
 * Main application JavaScript for Moodle Analytics Dashboard
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    initTooltips();
    
    // Initialize charts
    initCharts();
    
    // Load data
    loadDashboardData();
    
    // Set up auto-refresh every 5 minutes
    setInterval(loadDashboardData, 5 * 60 * 1000);
});

/**
 * Initialize tooltips using Tippy.js
 */
function initTooltips() {
    // Check if Tippy is loaded
    if (typeof tippy === 'function') {
        tippy('[data-tippy-content]', {
            placement: 'top',
            animation: 'scale',
            theme: 'light',
            arrow: true,
            delay: [100, 0],
            duration: 200,
            offset: [0, 10]
        });
    }
}

/**
 * Initialize charts using Chart.js
 */
function initCharts() {
    // Check if Chart.js is loaded
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js is not loaded');
        return;
    }
    
    // Activity Chart
    const activityCtx = document.getElementById('activity-chart');
    if (activityCtx) {
        new Chart(activityCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Active Users',
                    data: [65, 59, 80, 81, 56, 55],
                    borderColor: 'rgba(59, 130, 246, 1)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.3,
                    fill: true,
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: { size: 12 },
                        bodyFont: { size: 14 },
                        padding: 12,
                        usePointStyle: true,
                        callbacks: {
                            label: function(context) {
                                return ` ${context.parsed.y} users`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false,
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            stepSize: 20
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
}

/**
 * Load dashboard data from the server
 */
function loadDashboardData() {
    console.log('Loading dashboard data...');
    
    // Show loading state
    const statsCards = document.querySelectorAll('.stats-card');
    statsCards.forEach(card => {
        const valueElement = card.querySelector('.stat-value');
        if (valueElement) {
            valueElement.innerHTML = '<span class="loading"></span>';
        }
    });
    
    // Show loading state for top courses
    const topCoursesContainer = document.querySelector('.top-courses-container');
    if (topCoursesContainer) {
        topCoursesContainer.innerHTML = `
            <div class="col-span-full text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-purple-500 mb-2"></div>
                <p class="text-gray-400">Loading courses...</p>
            </div>
        `;
    }
    
    // Fetch data from the server
    fetch('api/get_stats.php')
        .then(response => {
            console.log('API response status:', response.status);
            if (!response.ok) {
                console.error('Response not OK, status:', response.status);
                return response.text().then(text => {
                    console.error('Response text:', text);
                    throw new Error(`HTTP error! status: ${response.status}, response: ${text}`);
                });
            }
            return response.json().catch(e => {
                console.error('Error parsing JSON:', e);
                return response.text().then(text => {
                    console.error('Raw response text:', text);
                    throw new Error('Invalid JSON response from server');
                });
            });
        })
        .then(data => {
            console.log('API response data:', data);
            if (!data) {
                throw new Error('No data received from server');
            }
            // Update the UI with the received data
            updateDashboard(data);
        })
        .catch(error => {
            console.error('Error loading dashboard data:', error);
            showNotification(`Error loading dashboard data: ${error.message}`, 'error');
            
            // Set default values in case of error
            updateDashboard({
                total_users: 0,
                total_courses: 0,
                total_categories: 0,
                active_users: 0,
                top_courses: []
            });
            
            // Show error in the top courses container
            if (topCoursesContainer) {
                topCoursesContainer.innerHTML = `
                    <div class="col-span-full text-center py-8">
                        <div class="text-red-500 mb-2">
                            <i class="fas fa-exclamation-triangle text-2xl"></i>
                        </div>
                        <p class="text-red-400">Failed to load courses</p>
                        <p class="text-sm text-gray-500 mt-1">${error.message}</p>
                    </div>
                `;
            }
        });
}

/**
 * Update the dashboard with new data
 * @param {Object} data - The data to update the dashboard with
 */
function updateDashboard(data) {
    console.log('Updating dashboard with data:', data);
    
    if (!data) {
        console.error('No data provided to updateDashboard');
        return;
    }
    
    // Check if data.data exists
    if (!data.data) {
        console.error('No data.data in response:', data);
        showNotification('Invalid data format received from server', 'error');
        return;
    }
    
    // Log the structure of the data
    console.log('Dashboard data structure:', {
        hasTopCourses: !!data.data.top_courses,
        topCoursesType: data.data.top_courses ? typeof data.data.top_courses : 'undefined',
        isArray: Array.isArray(data.data.top_courses),
        keys: Object.keys(data.data)
    });
    
    // Update stat cards
    try {
        if (typeof data.data.total_users !== 'undefined') {
            updateStatCard('total-users', data.data.total_users);
        } else {
            console.warn('total_users is undefined in response');
        }
        
        if (typeof data.data.active_users !== 'undefined') {
            updateStatCard('active-users', data.data.active_users);
        } else {
            console.warn('active_users is undefined in response');
        }
        
        if (typeof data.data.total_courses !== 'undefined') {
            updateStatCard('total-courses', data.data.total_courses);
        } else {
            console.warn('total_courses is undefined in response');
        }
        
        if (typeof data.data.total_categories !== 'undefined') {
            updateStatCard('total-categories', data.data.total_categories);
        } else {
            console.warn('total_categories is undefined in response');
        }
        
        // Update top courses if available
        if (data.data.top_courses) {
            console.log('Updating top courses with:', data.data.top_courses);
            updateTopCourses(data.data.top_courses);
        } else {
            console.warn('No top_courses in response data');
        }
    } catch (error) {
        console.error('Error updating dashboard:', error);
        showNotification(`Error updating dashboard: ${error.message}`, 'error');
    }
    
    if (data.recent_activity && Array.isArray(data.recent_activity)) {
        updateRecentActivity(data.recent_activity);
    }
    
    // Update top enrolled courses if data is available
    console.log('Top courses data:', data.top_courses);
    if (data.top_courses && Array.isArray(data.top_courses)) {
        console.log(`Updating ${data.top_courses.length} top courses`);
        updateTopCourses(data.top_courses);
    } else {
        console.error('No top courses data found or invalid format:', data.top_courses);
    }
}

/**
 * Update a single stat card
 * @param {string} id - The ID of the stat card element
 * @param {number} value - The value to display
 */
function updateStatCard(id, value) {
    const element = document.getElementById(id);
    if (element) {
        // Animate the number change
        const start = parseInt(element.textContent.replace(/,/g, '')) || 0;
        const end = value;
        const duration = 1000; // Animation duration in ms
        const stepTime = 20; // Time between each step in ms
        
        const steps = Math.ceil(duration / stepTime);
        const increment = (end - start) / steps;
        let current = start;
        
        const timer = setInterval(() => {
            current += increment;
            if ((increment >= 0 && current >= end) || (increment < 0 && current <= end)) {
                clearInterval(timer);
                current = end;
            }
            element.textContent = Math.round(current).toLocaleString();
        }, stepTime);
    }
}

/**
 * Update the recent activity list
 * @param {Array} activities - Array of activity objects
 */
function updateRecentActivity(activities) {
    const container = document.getElementById('recent-activity');
    if (!container) return;
    
    // Clear existing content
    container.innerHTML = '';
    
    // Add new activities
    activities.forEach(activity => {
        const activityElement = document.createElement('div');
        activityElement.className = 'flex items-start mb-4';
        activityElement.innerHTML = `
            <div class="p-2 ${getActivityIconColor(activity.type)} rounded-lg mr-3">
                <i class="${getActivityIcon(activity.type)}"></i>
            </div>
            <div>
                <p class="text-sm font-medium">${activity.message}</p>
                <p class="text-xs text-gray-500">${formatTimeAgo(activity.timestamp)}</p>
            </div>
        `;
        container.appendChild(activityElement);
    });
}

/**
 * Get the appropriate icon for an activity type
 * @param {string} type - The activity type
 * @returns {string} The icon class
 */
function getActivityIcon(type) {
    const icons = {
        'user': 'fas fa-user-plus',
        'course': 'fas fa-book',
        'enrollment': 'fas fa-user-graduate',
        'quiz': 'fas fa-question-circle',
        'assignment': 'fas fa-tasks',
        'forum': 'fas fa-comment',
        'default': 'fas fa-bell'
    };
    
    return icons[type] || icons['default'];
}

/**
 * Get the appropriate color class for an activity icon
 * @param {string} type - The activity type
 * @returns {string} The color class
 */
function getActivityIconColor(type) {
    const colors = {
        'user': 'bg-blue-100 text-blue-600',
        'course': 'bg-green-100 text-green-600',
        'enrollment': 'bg-purple-100 text-purple-600',
        'quiz': 'bg-yellow-100 text-yellow-600',
        'assignment': 'bg-indigo-100 text-indigo-600',
        'forum': 'bg-pink-100 text-pink-600',
        'default': 'bg-gray-100 text-gray-600'
    };
    
    return colors[type] || colors['default'];
}

/**
 * Format a timestamp as a relative time string (e.g., '2 minutes ago')
 * @param {string|number} timestamp - The timestamp to format
 * @returns {string} The formatted time string
 */
function formatTimeAgo(timestamp) {
    const seconds = Math.floor((new Date() - new Date(timestamp)) / 1000);
    
    const intervals = {
        year: 31536000,
        month: 2592000,
        week: 604800,
        day: 86400,
        hour: 3600,
        minute: 60,
        second: 1
    };
    
    for (const [unit, secondsInUnit] of Object.entries(intervals)) {
        const interval = Math.floor(seconds / secondsInUnit);
        if (interval >= 1) {
            return interval === 1 ? `1 ${unit} ago` : `${interval} ${unit}s ago`;
        }
    }
    
    return 'just now';
}

/**
 * Show a notification to the user
 * @param {string} message - The message to display
 * @param {string} type - The type of notification (success, error, warning, info)
 */
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg text-white ${getNotificationClass(type)}`;
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="${getNotificationIcon(type)} mr-2"></i>
            <span>${message}</span>
            <button class="ml-4 text-white opacity-75 hover:opacity-100" onclick="this.parentElement.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    // Add to the document
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

/**
 * Get the appropriate CSS class for a notification type
 * @param {string} type - The notification type
 * @returns {string} The CSS class
 */
function getNotificationClass(type) {
    const classes = {
        'success': 'bg-green-500',
        'error': 'bg-red-500',
        'warning': 'bg-yellow-500',
        'info': 'bg-blue-500',
        'default': 'bg-gray-500'
    };
    
    return classes[type] || classes['default'];
}

/**
 * Get the appropriate icon for a notification type
 * @param {string} type - The notification type
 * @returns {string} The icon class
 */
function getNotificationIcon(type) {
    const icons = {
        'success': 'fas fa-check-circle',
        'error': 'fas fa-exclamation-circle',
        'warning': 'fas fa-exclamation-triangle',
        'info': 'fas fa-info-circle',
        'default': 'fas fa-bell'
    };
    
    return icons[type] || icons['default'];
}

/**
 * Update the top enrolled courses section
 * @param {Array} courses - Array of course objects
 */
function updateTopCourses(courses) {
    console.log('Updating top courses with data:', courses);
    
    // Debug: Log all course objects to inspect their structure
    if (courses && courses.length > 0) {
        console.log('All course objects:');
        courses.forEach((course, index) => {
            console.log(`Course #${index + 1}:`, {
                id: course.id,
                fullname: course.fullname,
                categoryId: course.categoryid || course.category,
                categoryName: course.categoryname,
                allProperties: Object.keys(course)
            });
        });
    }
    
    const container = document.querySelector('.top-courses-container');
    if (!container) {
        console.error('Top courses container not found');
        return;
    }
    
    // Clear existing content
    container.innerHTML = '';
    
    if (!Array.isArray(courses)) {
        console.error('Courses data is not an array:', courses);
        container.innerHTML = `
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 text-center">
                <div class="text-red-500 mb-2">
                    <i class="fas fa-exclamation-triangle text-2xl"></i>
                </div>
                <p class="text-red-600 font-medium">Invalid courses data format</p>
                <p class="text-sm text-gray-500 mt-1">Expected an array but got: ${typeof courses}</p>
            </div>
        `;
        return;
    }
    
    if (courses.length === 0) {
        console.log('No courses found to display');
        container.innerHTML = `
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center">
                <div class="text-gray-400 mb-3">
                    <i class="fas fa-book-open text-4xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-700">No enrolled courses found</h3>
                <p class="text-gray-500 mt-1">There are no courses with enrolled users to display</p>
            </div>
        `;
        return;
    }
    
    // Create table container - using col-span-full to break out of the grid
    const tableContainer = document.createElement('div');
    tableContainer.className = 'col-span-full w-full bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden';
    
    // Create table
    const table = document.createElement('table');
    table.className = 'min-w-full divide-y divide-gray-200';
    
    // Create table header
    const thead = document.createElement('thead');
    thead.className = 'bg-gray-50';
    thead.innerHTML = `
        <tr>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course Name</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Students</th>
        </tr>
    `;
    
    // Create table body
    const tbody = document.createElement('tbody');
    tbody.className = 'bg-white divide-y divide-gray-200';
    
    // Add courses to table
    courses.forEach((course, index) => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50 transition-colors cursor-pointer';
        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                ${index + 1}
            </td>
            <td class="px-6 py-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-book text-blue-600"></i>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900">${escapeHtml(course.fullname || 'Unnamed Course')}</div>
                        <div class="text-xs text-gray-500">${escapeHtml(course.shortname || '')}</div>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                    ${course.category ? escapeHtml(course.category.name || course.category) : 'Uncategorized'}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${getEnrollmentBadgeClass(course.enrolledusercount)}">
                    <i class="fas fa-users mr-1.5"></i>
                    ${course.enrolledusercount || 0} enrolled
                </span>
            </td>
        `;
        
        // Add click handler
        row.addEventListener('click', () => {
            console.log('Navigating to course:', course.id);
            // Add navigation logic here
        });
        
        tbody.appendChild(row);
    });
    
    // Assemble table
    table.appendChild(thead);
    table.appendChild(tbody);
    tableContainer.appendChild(table);
    container.appendChild(tableContainer);
}

// Helper function to escape HTML (from courses.js)
function escapeHtml(unsafe) {
    if (!unsafe) return '';
    return unsafe
        .toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Helper function to get enrollment badge class (from courses.js)
function getEnrollmentBadgeClass(count) {
    if (!count) return 'bg-gray-100 text-gray-800';
    if (count > 50) return 'bg-green-100 text-green-800';
    if (count > 20) return 'bg-blue-100 text-blue-800';
    return 'bg-amber-100 text-amber-800';
}

/**
 * Generate a random badge color class
 * @returns {string} Tailwind CSS class for badge color
 */
function getRandomBadgeColor() {
    const colors = [
        'bg-purple-900 bg-opacity-50 text-purple-300',
        'bg-blue-900 bg-opacity-50 text-blue-300',
        'bg-green-900 bg-opacity-50 text-green-300',
        'bg-amber-900 bg-opacity-50 text-amber-300',
        'bg-pink-900 bg-opacity-50 text-pink-300',
        'bg-cyan-900 bg-opacity-50 text-cyan-300'
    ];
    return colors[Math.floor(Math.random() * colors.length)];
}

// Make showNotification available globally
window.showNotification = showNotification;