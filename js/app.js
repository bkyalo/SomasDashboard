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
    // Show loading state
    const statsCards = document.querySelectorAll('.stats-card');
    statsCards.forEach(card => {
        const valueElement = card.querySelector('.stat-value');
        if (valueElement) {
            valueElement.innerHTML = '<span class="loading"></span>';
        }
    });
    
    // Fetch data from the server
    fetch('api/get_stats.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Update the UI with the received data
            updateDashboard(data);
        })
        .catch(error => {
            console.error('Error loading dashboard data:', error);
            showNotification('Error loading dashboard data. Please try again later.', 'error');
            
            // Set default values in case of error
            updateDashboard({
                total_users: 0,
                total_courses: 0,
                total_categories: 0,
                active_users: 0
            });
        });
}

/**
 * Update the dashboard with new data
 * @param {Object} data - The data to update the dashboard with
 */
function updateDashboard(data) {
    // Update stats cards
    updateStatCard('total-users', data.total_users || 0);
    updateStatCard('total-courses', data.total_courses || 0);
    updateStatCard('total-categories', data.total_categories || 0);
    updateStatCard('active-users', data.active_users || 0);
    
    // Update recent activity
    if (data.recent_activity && Array.isArray(data.recent_activity)) {
        updateRecentActivity(data.recent_activity);
    }
    
    // Update top enrolled courses if data is available
    if (data.top_courses && Array.isArray(data.top_courses)) {
        updateTopCourses(data.top_courses);
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
    const container = document.querySelector('.top-courses-container');
    if (!container) return;
    
    // Clear existing content
    container.innerHTML = '';
    
    if (courses.length === 0) {
        container.innerHTML = `
            <div class="col-span-full text-center py-8">
                <p class="text-gray-400">No enrolled courses found</p>
            </div>
        `;
        return;
    }
    
    // Create and append course cards
    courses.forEach((course, index) => {
        const courseElement = document.createElement('div');
        courseElement.className = 'group relative bg-gray-800 rounded-xl p-5 hover:bg-gray-750 transition-all duration-300 transform hover:-translate-y-1 hover:shadow-lg hover:shadow-purple-500/10 border border-gray-700 overflow-hidden';
        
        // Add glow effect for top 3 courses
        if (index < 3) {
            const glowColors = [
                'from-purple-500 to-pink-500',
                'from-blue-400 to-cyan-400',
                'from-amber-400 to-orange-500'
            ];
            courseElement.innerHTML += `
                <div class="absolute -inset-0.5 bg-gradient-to-r ${glowColors[index]} rounded-xl opacity-0 group-hover:opacity-30 blur transition duration-1000 group-hover:duration-200"></div>
            `;
        }
        
        // Course content
        courseElement.innerHTML += `
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <span class="px-2 py-1 text-xs font-medium rounded-full ${getRandomBadgeColor()}">
                        ${course.categoryname || 'Uncategorized'}
                    </span>
                    <span class="text-xs text-gray-400">
                        <i class="fas fa-users mr-1"></i> ${course.enrolledusercount || 0}
                    </span>
                </div>
                <h3 class="font-bold text-white mb-2 line-clamp-2" title="${course.fullname || 'Unnamed Course'}">
                    ${course.fullname || 'Unnamed Course'}
                </h3>
                <p class="text-sm text-gray-400 line-clamp-2 mb-4">
                    ${course.summary || 'No description available'}
                </p>
                <div class="flex justify-between items-center">
                    <span class="text-xs text-gray-500">
                        ID: ${course.id}
                    </span>
                    <a href="#" class="text-purple-400 hover:text-purple-300 text-sm font-medium transition-colors">
                        View <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        `;
        
        container.appendChild(courseElement);
    });
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