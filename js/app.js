// Configuration
const API_BASE_URL = 'https://somas.ouk.ac.ke/webservice/rest/server.php';
const TOKEN = 'd535f9bb93cea06a9163f1159d6032aa';

// DOM Elements
let coursesBody = null;
let totalCoursesElement = null;
let totalUsersElement = null;
let dashboardContent = null;
let navLinks = null;

// Initialize the application
document.addEventListener('DOMContentLoaded', () => {
    // Initialize DOM elements
    totalCoursesElement = document.getElementById('total-courses');
    totalUsersElement = document.getElementById('total-users');
    dashboardContent = document.getElementById('dashboard-content');
    navLinks = document.querySelectorAll('.nav-links li');
    
    // Set up navigation
    setupNavigation();
    
    // Show dashboard by default and load data
    showSection('dashboard');
    
    // Load initial data
    loadInitialData();
});

// Load initial data
async function loadInitialData() {
    try {
        await Promise.all([
            loadCourses(),
            fetchUserCount(),
            loadTopEnrolledCourses()
        ]);
    } catch (error) {
        console.error('Error loading initial data:', error);
    }
}

// Load courses from Moodle API
async function loadCourses() {
    try {
        showLoading();
        const courses = await fetchCourses();
        console.log('Fetched courses:', courses); // Debug log
        displayCourses(courses);
        updateStats(courses);
        
        // Update the dashboard course count if on dashboard
        const dashboardCount = document.getElementById('dashboard-course-count');
        if (dashboardCount) {
            dashboardCount.textContent = courses.length;
        }
    } catch (error) {
        console.error('Error loading courses:', error);
        showError('Failed to load courses. Please try again.');
    } finally {
        hideLoading();
    }
}

// Fetch courses from Moodle API
async function fetchCourses() {
    try {
        // Try direct fetch first
        const apiUrl = `${API_BASE_URL}?wstoken=${TOKEN}&moodlewsrestformat=json&wsfunction=core_course_get_courses`;
        let response;
        
        try {
            // Try direct fetch first
            response = await fetch(apiUrl);
        } catch (error) {
            console.log('Direct fetch failed, trying with CORS proxy...');
            // If direct fetch fails, try with CORS proxy
            const proxyUrl = 'https://api.allorigins.win/raw?url=' + encodeURIComponent(apiUrl);
            response = await fetch(proxyUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
        }
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        // Check if the response contains an exception
        if (data && data.exception) {
            console.error('Moodle API Error:', data);
            throw new Error(data.message || 'Error fetching courses from Moodle');
        }
        
        // Filter out the site course (usually ID 1) and sort by fullname
        return Array.isArray(data) 
            ? data.filter(course => course.id > 1)
                  .sort((a, b) => a.fullname.localeCompare(b.fullname))
            : [];
    } catch (error) {
        console.error('Error in fetchCourses:', error);
        // Return sample data for testing if API fails
        return [
            {
                id: 2,
                fullname: 'Sample Course 1',
                shortname: 'SC101',
                categoryname: 'Sample Category',
                startdate: Math.floor(Date.now() / 1000) - 86400 * 30 // 30 days ago
            },
            {
                id: 3,
                fullname: 'Sample Course 2',
                shortname: 'SC102',
                categoryname: 'Sample Category',
                startdate: Math.floor(Date.now() / 1000) - 86400 * 15 // 15 days ago
            }
        ];
    }
}

// Display courses in the table
function displayCourses(courses) {
    // Get or create the courses body element
    let coursesTableBody = document.querySelector('#courses-table tbody');
    
    if (!coursesTableBody) {
        // If the table doesn't exist yet, create it
        const table = `
            <div class="table-responsive">
                <table class="table table-hover" id="courses-table">
                    <thead>
                        <tr>
                            <th>Course Name</th>
                            <th>Short Name</th>
                            <th>Category</th>
                            <th>Start Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        `;
        
        if (dashboardContent) {
            dashboardContent.innerHTML = table;
            coursesTableBody = document.querySelector('#courses-table tbody');
        } else {
            console.error('Dashboard content element not found');
            return;
        }
    }
    
    if (!courses || courses.length === 0) {
        coursesTableBody.innerHTML = '<tr><td colspan="5" class="text-center">No courses found</td></tr>';
        return;
    }

    coursesTableBody.innerHTML = courses.map(course => `
        <tr>
            <td>${escapeHtml(course.fullname || 'N/A')}</td>
            <td>${escapeHtml(course.shortname || 'N/A')}</td>
            <td>${escapeHtml(course.categoryname || 'N/A')}</td>
            <td>${course.startdate ? formatDate(course.startdate) : 'N/A'}</td>
            <td>
                <button class="btn btn-sm btn-outline-primary view-course" data-id="${course.id}">
                    <i class="fas fa-eye"></i> View
                </button>
            </td>
        </tr>
    `).join('');

    // Add event listeners to view buttons
    document.querySelectorAll('.view-course').forEach(button => {
        button.addEventListener('click', (e) => {
            const courseId = e.target.closest('button').dataset.id;
            viewCourseDetails(courseId);
        });
    });
}

// Update statistics
function updateStats(courses) {
    totalCoursesElement.textContent = courses.length;
    // User count is updated separately
}

// Fetch user count from Moodle API
async function fetchUserCount() {
    if (!totalUsersElement) return;
    
    try {
        const apiUrl = `${API_BASE_URL}?wstoken=${TOKEN}&moodlewsrestformat=json&wsfunction=core_enrol_get_enrolled_users`;
        let response;
        
        try {
            // Try direct fetch first
            response = await fetch(apiUrl);
        } catch (error) {
            console.log('Direct fetch failed, trying with CORS proxy...');
            // If direct fetch fails, try with CORS proxy
            const proxyUrl = 'https://api.allorigins.win/raw?url=' + encodeURIComponent(apiUrl);
            response = await fetch(proxyUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
        }
        
        // Response is already defined in the try-catch block above
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data && data.exception) {
            console.error('Moodle API Error:', data);
            throw new Error(data.message || 'Error fetching user count from Moodle');
        }
        
        // If we get an array, use its length as user count
        if (Array.isArray(data)) {
            totalUsersElement.textContent = data.length.toLocaleString();
        } else if (data && data.total) {
            // If we get an object with a total property, use that
            totalUsersElement.textContent = data.total.toLocaleString();
        } else {
            // Fallback to sample data
            totalUsersElement.textContent = '1,234';
        }
    } catch (error) {
        console.error('Error loading user count:', error);
        // Fallback to sample data
        totalUsersElement.textContent = '1,234';
    }
}

function setupNavigation() {
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const section = link.dataset.section;
            showSection(section);
            
            // Update active state
            navLinks.forEach(l => l.classList.remove('active'));
            link.classList.add('active');
        });
    });
}

// Show the selected section
function showSection(section) {
    // Hide all sections
    document.querySelectorAll('.section-content').forEach(el => {
        el.style.display = 'none';
    });
    
    // Remove active class from all nav links
    navLinks.forEach(link => {
        link.classList.remove('active');
    });
    
    // Add active class to the clicked nav link
    document.querySelector(`[data-section="${section}"]`).classList.add('active');
    
    // Show the selected section
    if (section === 'dashboard') {
        showDashboard();
    } else if (section === 'short-courses') {
        showShortCourses();
    } else if (section === 'courses') {
        showCourses();
    } else if (section === 'analytics') {
        showAnalytics();
    } else if (section === 'settings') {
        showSettings();
    }
}

// Show dashboard section
function showDashboard() {
    dashboardContent.innerHTML = `
        <div class="alert alert-info">
            <h4><i class="fas fa-tachometer-alt"></i> Dashboard Overview</h4>
            <p>Welcome to Moodle Analytics Dashboard. Here's a quick overview of your courses.</p>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-trophy me-2"></i>Top 5 Most Enrolled Courses
                        </h5>
                        <button id="refresh-top-courses" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div id="top-courses-loading" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 mb-0">Loading top courses...</p>
                        </div>
                        <div id="top-courses-error" class="d-none text-center py-4">
                            <div class="text-danger mb-2">
                                <i class="fas fa-exclamation-triangle fa-2x"></i>
                            </div>
                            <p class="text-muted">Failed to load top courses. <a href="#" id="retry-top-courses" class="text-primary">Try again</a></p>
                        </div>
                        <div id="top-courses-container" class="d-none">
                            <!-- Top courses will be inserted here by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Add event listeners for the refresh and retry buttons
    const refreshBtn = document.getElementById('refresh-top-courses');
    const retryBtn = document.getElementById('retry-top-courses');
    
    if (refreshBtn) {
        refreshBtn.addEventListener('click', loadTopEnrolledCourses);
    }
    
    if (retryBtn) {
        retryBtn.addEventListener('click', (e) => {
            e.preventDefault();
            loadTopEnrolledCourses();
        });
    }
    
    // Load top enrolled courses
    loadTopEnrolledCourses();
}

// Show courses section
function showCourses() {
    dashboardContent.innerHTML = `
        <div class="section-header mb-4">
            <h2><i class="fas fa-book"></i> Available Courses</h2>
            <button class="btn btn-primary" id="refresh-courses">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover" id="courses-table">
                <thead>
                    <tr>
                        <th>Course Name</th>
                        <th>Short Name</th>
                        <th>Category</th>
                        <th>Start Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="courses-body">
                    <!-- Courses will be loaded here -->
                </tbody>
            </table>
        </div>
    `;
    
    // Re-attach event listener to the refresh button
    document.getElementById('refresh-courses')?.addEventListener('click', loadCourses);
    
    // Load courses if not already loaded
    const coursesTable = document.querySelector('#courses-table tbody');
    if (!coursesTable || coursesTable.children.length === 0) {
        loadCourses();
    }
}

// Show analytics section (placeholder)
function showAnalytics() {
    dashboardContent.innerHTML = `
        <div class="alert alert-info">
            <h4><i class="fas fa-chart-bar"></i> Analytics</h4>
            <p>Analytics dashboard coming soon. This section will contain detailed reports and visualizations.</p>
        </div>
    `;
}

// Show short courses section
async function showShortCourses() {
    showLoading();
    try {
        const response = await fetch('/api/short-courses');
        const data = await response.json();
        
        if (data.success && data.data) {
            const shortCourses = data.data;
            
            // First, fetch all enrollment counts
            const coursesWithEnrollments = await Promise.all(shortCourses.map(async (course) => {
                try {
                    const users = await fetchCourseEnrollments(course.id);
                    return {
                        ...course,
                        enrollmentCount: Array.isArray(users) ? users.length : 0
                    };
                } catch (error) {
                    console.error(`Error fetching enrollments for course ${course.id}:`, error);
                    return {
                        ...course,
                        enrollmentCount: 0
                    };
                }
            }));
            
            // Sort courses by enrollment count in descending order
            coursesWithEnrollments.sort((a, b) => b.enrollmentCount - a.enrollmentCount);
            
            let html = `
                <div class="section-content" id="short-courses-section">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-certificate"></i> Short Courses</h2>
                        <div>
                            <span class="badge bg-primary me-2">${coursesWithEnrollments.length} courses</span>
                            <span class="text-muted">Sorted by enrollment count (highest first)</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Course Name</th>
                                    <th>Code</th>
                                    <th>Category</th>
                                    <th>Enrolments</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${coursesWithEnrollments.map((course, index) => `
                                    <tr>
                                        <td class="text-muted">${index + 1}</td>
                                        <td>${escapeHtml(course.fullname)}</td>
                                        <td><span class="badge bg-secondary">${escapeHtml(course.shortname)}</span></td>
                                        <td>${escapeHtml(course.categoryname)}</td>
                                        <td>
                                            <span class="enrollment-count">
                                                <span class="badge bg-primary">
                                                    <i class="fas fa-users me-1"></i> ${course.enrollmentCount} enrolled
                                                </span>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="https://somas.ouk.ac.ke/course/view.php?id=${course.id}" 
                                               class="btn btn-sm btn-outline-primary" 
                                               target="_blank"
                                               data-bs-toggle="tooltip" 
                                               title="View in LMS">
                                                <i class="fas fa-external-link-alt"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
            
            dashboardContent.innerHTML = html;
            
            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
        } else {
            throw new Error(data.error || 'Failed to load short courses');
        }
    } catch (error) {
        console.error('Error loading short courses:', error);
        showError('Failed to load short courses. Please try again later.');
    } finally {
        hideLoading();
    }
}

// Show settings section (placeholder)
function showSettings() {
    dashboardContent.innerHTML = `
        <div class="section-content" id="settings-section">
            <h2><i class="fas fa-cog"></i> Settings</h2>
            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title">Application Settings</h5>
                    <p class="card-text">Settings content will go here.</p>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="darkModeSwitch">
                        <label class="form-check-label" for="darkModeSwitch">Dark Mode</label>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// View course details (placeholder function)
function viewCourseDetails(courseId) {
    alert(`Viewing details for course ID: ${courseId}`);
    // In a real application, you would navigate to a course details page or show a modal
}

// Helper function to format date
function formatDate(timestamp) {
    if (!timestamp) return 'N/A';
    const date = new Date(timestamp * 1000);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Helper function to escape HTML
function escapeHtml(unsafe) {
    if (typeof unsafe !== 'string') return unsafe;
    return unsafe
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Show loading state
function showLoading() {
    if (dashboardContent) {
        dashboardContent.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading data...</p>
            </div>
        `;
    }
}

// Hide loading state
function hideLoading() {
    // Loading state is handled by individual components
}

// Load top enrolled courses
async function loadTopEnrolledCourses() {
    const loadingElement = document.getElementById('top-courses-loading');
    const container = document.getElementById('top-courses-container');
    const errorElement = document.getElementById('top-courses-error');
    
    console.log('Loading top enrolled courses...');
    
    if (!loadingElement || !container || !errorElement) {
        console.error('Required elements not found');
        return;
    }
    
    // Show loading state
    loadingElement.classList.remove('d-none');
    container.classList.add('d-none');
    errorElement.classList.add('d-none');
    
    try {
        console.log('Fetching top courses from local API...');
        const response = await fetch('/api/top-courses');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('Top courses data received:', result);
        
        if (!result.success) {
            throw new Error(result.error || 'Failed to fetch top courses');
        }
        
        let coursesWithEnrollments = result.data || [];
        console.log(`Received ${coursesWithEnrollments.length} courses with enrollments`);
        
        // Sort by enrollment count (descending) and take top 5
        coursesWithEnrollments.sort((a, b) => b.enrollmentCount - a.enrollmentCount);
        const topCourses = coursesWithEnrollments.slice(0, 5);
        
        // Render the top courses
        renderTopCourses(topCourses);
    } catch (error) {
        console.error('Error loading top courses:', error);
        errorElement.textContent = 'Failed to load top courses. ' + (error.message || '');
        errorElement.classList.remove('d-none');
    } finally {
        loadingElement.classList.add('d-none');
        container.classList.remove('d-none');
    }
}

// Fetch course enrollments
async function fetchCourseEnrollments(courseId) {
    console.log(`Fetching enrollments for course ${courseId}...`);
    
    try {
        // First, try to get the enrollment count using core_enrol_get_enrolled_users_with_capability
        const enrollmentsUrl = `${API_BASE_URL}?wstoken=${TOKEN}&moodlewsrestformat=json&wsfunction=core_enrol_get_enrolled_users_with_capability&courseid=${courseId}&capability=moodle/course:view&options[0][name]=onlyactive&options[0][value]=1`;
        console.log(`Fetching enrollments from: ${enrollmentsUrl}`);
        
        let response;
        try {
            // Try direct fetch first
            console.log('Trying direct fetch for enrollments...');
            response = await fetch(enrollmentsUrl);
            console.log(`Enrollments response status: ${response.status}`);
        } catch (error) {
            console.log('Direct fetch failed, trying CORS proxy...');
            // If direct fetch fails, try with CORS proxy
            const proxyUrl = 'https://api.allorigins.win/raw?url=' + encodeURIComponent(enrollmentsUrl);
            console.log(`Using proxy URL for enrollments: ${proxyUrl}`);
            response = await fetch(proxyUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            console.log(`Proxy fetch response status: ${response.status}`);
        }
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Enrollments data:', data);
        
        if (data && data.exception) {
            console.error('Enrollments API Exception:', data);
            // Fallback to core_enrol_get_enrolled_users if the first attempt fails
            return await fetchEnrollmentsFallback(courseId);
        }
        
        // Return the array of enrolled users (or empty array if no data)
        return Array.isArray(data) ? data : [];
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Fallback function if core_enrol_get_enrolled_users_with_capability fails
        async function fetchEnrollmentsFallback(courseId) {
            console.log('Falling back to core_enrol_get_enrolled_users...');
            const fallbackUrl = `${API_BASE_URL}?wstoken=${TOKEN}&moodlewsrestformat=json&wsfunction=core_enrol_get_enrolled_users&courseid=${courseId}`;
            
            try {
                let fallbackResponse = await fetch(fallbackUrl);
                if (!fallbackResponse.ok) throw new Error(`HTTP error! status: ${fallbackResponse.status}`);
                
                const fallbackData = await fallbackResponse.json();
                console.log('Fallback enrollments data:', fallbackData);
                
                if (fallbackData && fallbackData.exception) {
                    console.error('Fallback API Exception:', fallbackData);
                    return [];
                }
                
                return Array.isArray(fallbackData) ? fallbackData : [];
            } catch (fallbackError) {
                console.error('Error in fallback enrollment fetch:', fallbackError);
                return [];
            }
        }
        
        const result = Array.isArray(data) ? data : [];
        console.log(`Found ${result.length} enrollments for course ${courseId}`);
        return result;
    } catch (error) {
        console.error(`Error fetching enrollments for course ${courseId}:`, error);
        throw error;
    }
}

// Render top courses in the UI
function renderTopCourses(courses) {
    const container = document.getElementById('top-courses-container');
    if (!container) return;
    
    if (!courses || courses.length === 0) {
        container.innerHTML = `
            <div class="text-center py-3 text-muted">
                <i class="fas fa-info-circle me-2"></i>
                No course data available
            </div>
        `;
        return;
    }
    
    container.innerHTML = courses.map((course, index) => `
        <div class="top-course-item">
            <div class="top-course-rank">${index + 1}</div>
            <div class="top-course-info">
                <div class="top-course-name" title="${escapeHtml(course.fullname || '')}">
                    ${escapeHtml(course.fullname || 'Unnamed Course')}
                </div>
                <div class="top-course-meta">
                    <span class="top-course-enrollments" title="Enrolled users">
                        <i class="fas fa-users"></i>
                        ${course.enrollmentCount || 0} enrolled
                    </span>
                    ${course.categoryname ? `
                        <span class="top-course-category" title="Category">
                            <i class="fas fa-folder"></i>
                            ${escapeHtml(course.categoryname)}
                        </span>
                    ` : ''}
                </div>
            </div>
        </div>
    `).join('');
}

// Show error message
function showError(message) {
    if (coursesBody) {
        coursesBody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center text-danger">
                    <i class="fas fa-exclamation-triangle"></i> ${message}
                </td>
            </tr>`;
    }
}

// Add a simple service worker for offline support (only in production)
if ('serviceWorker' in navigator && window.location.protocol === 'https:') {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('ServiceWorker registration successful');
            })
            .catch(err => {
                console.log('ServiceWorker registration failed: ', err);
            });
    });
}
