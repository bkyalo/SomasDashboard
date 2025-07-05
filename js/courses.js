document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const coursesTable = document.getElementById('coursesTable');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const noCoursesMessage = document.getElementById('noCoursesMessage');
    const pagination = document.getElementById('pagination');
    const searchInput = document.getElementById('searchInput');
    const refreshButton = document.getElementById('refreshButton');
    const rowsPerPageSelect = document.getElementById('rowsPerPage');
    
    // State
    let currentPage = 1;
    let rowsPerPage = parseInt(rowsPerPageSelect.value);
    let allCourses = [];
    let filteredCourses = [];
    
    // Initialize the page
    init();
    
    // Event Listeners
    searchInput.addEventListener('input', debounce(handleSearch, 300));
    refreshButton.addEventListener('click', init);
    rowsPerPageSelect.addEventListener('change', handleRowsPerPageChange);
    
    // Functions
    async function init() {
        try {
            showLoading(true);
            await fetchCourses();
            renderTable();
            renderPagination();
        } catch (error) {
            console.error('Error initializing courses page:', error);
            showError('Failed to load courses. Please try again.');
        } finally {
            showLoading(false);
        }
    }
    
    async function fetchCourses() {
        try {
            const response = await fetch(`api/get_courses.php?page=${currentPage}&per_page=${rowsPerPage}&search=${encodeURIComponent(searchInput.value)}`);
            const data = await response.json();
            
            if (!response.ok || !data.success) {
                throw new Error(data.error || 'Failed to fetch courses');
            }
            
            allCourses = data.data;
            updateUI();
            return data;
        } catch (error) {
            console.error('Error fetching courses:', error);
            showError('Failed to fetch courses. Please try again.');
            throw error;
        }
    }
    
    function renderTable() {
        if (!allCourses || allCourses.length === 0) {
            coursesTable.querySelector('tbody').innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-8 text-gray-500">
                        No courses found
                    </td>
                </tr>
            `;
            return;
        }
        
        const tbody = coursesTable.querySelector('tbody');
        tbody.innerHTML = '';
        
        allCourses.forEach(course => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50 transition-colors';
            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-book text-blue-600"></i>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">${escapeHtml(course.fullname)}</div>
                            <div class="text-xs text-gray-500">${escapeHtml(course.categoryname)}</div>
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
            `;
            tbody.appendChild(row);
        });
    }
    
    function renderPagination(paginationData) {
        if (!paginationData || paginationData.last_page <= 1) {
            pagination.innerHTML = '';
            return;
        }
        
        const { current_page, last_page } = paginationData;
        
        let paginationHTML = `
            <div class="flex items-center justify-between px-4 py-3 bg-white border-t border-gray-200 sm:px-6">
                <div class="flex justify-between flex-1 sm:hidden">
                    <button 
                        onclick="changePage(${current_page - 1})" 
                        ${current_page === 1 ? 'disabled' : ''}
                        class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                    >
                        Previous
                    </button>
                    <button 
                        onclick="changePage(${current_page + 1})" 
                        ${current_page === last_page ? 'disabled' : ''}
                        class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                    >
                        Next
                    </button>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium">${(current_page - 1) * rowsPerPage + 1}</span> to 
                            <span class="font-medium">${Math.min(current_page * rowsPerPage, paginationData.total)}</span> of 
                            <span class="font-medium">${paginationData.total}</span> results
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                            <button 
                                onclick="changePage(${current_page - 1})" 
                                ${current_page === 1 ? 'disabled' : ''}
                                class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50"
                            >
                                <span class="sr-only">Previous</span>
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            ${Array.from({ length: Math.min(5, last_page) }, (_, i) => {
                                const pageNum = i + 1;
                                const isActive = pageNum === current_page;
                                return `
                                    <button 
                                        onclick="changePage(${pageNum})" 
                                        class="relative inline-flex items-center px-4 py-2 text-sm font-medium ${
                                            isActive 
                                                ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' 
                                                : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                        } border"
                                    >
                                        ${pageNum}
                                    </button>
                                `;
                            }).join('')}
                            ${last_page > 5 ? `
                                <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300">
                                    ...
                                </span>
                                <button 
                                    onclick="changePage(${last_page})" 
                                    class="relative inline-flex items-center px-4 py-2 text-sm font-medium ${
                                        current_page === last_page 
                                            ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' 
                                            : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                    } border"
                                >
                                    ${last_page}
                                </button>
                            ` : ''}
                            <button 
                                onclick="changePage(${current_page + 1})" 
                                ${current_page === last_page ? 'disabled' : ''}
                                class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50"
                            >
                                <span class="sr-only">Next</span>
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </nav>
                    </div>
                </div>
            </div>
        `;
        
        pagination.innerHTML = paginationHTML;
    }
    
    function handleSearch() {
        currentPage = 1;
        fetchCourses();
    }
    
    function handleRowsPerPageChange(e) {
        rowsPerPage = parseInt(e.target.value);
        currentPage = 1;
        fetchCourses();
    }
    
    function changePage(page) {
        if (page < 1 || page > Math.ceil(filteredCourses.length / rowsPerPage)) return;
        currentPage = page;
        fetchCourses();
    }
    
    function updateUI() {
        renderTable();
        renderPagination({
            current_page: currentPage,
            last_page: Math.ceil(filteredCourses.length / rowsPerPage),
            total: filteredCourses.length
        });
    }
    
    function showLoading(show) {
        if (show) {
            loadingIndicator.classList.remove('hidden');
            coursesTable.classList.add('opacity-50', 'pointer-events-none');
        } else {
            loadingIndicator.classList.add('hidden');
            coursesTable.classList.remove('opacity-50', 'pointer-events-none');
        }
    }
    
    function showError(message) {
        const alert = document.createElement('div');
        alert.className = 'bg-red-50 border-l-4 border-red-400 p-4 mb-4';
        alert.innerHTML = `
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700">
                        ${escapeHtml(message)}
                    </p>
                </div>
            </div>
        `;
        
        const container = document.querySelector('.container.mx-auto.px-4.py-8');
        container.insertBefore(alert, container.firstChild);
        
        // Remove the alert after 5 seconds
        setTimeout(() => {
            alert.remove();
        }, 5000);
    }
    
    // Utility functions
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
    
    function escapeHtml(unsafe) {
        if (unsafe === null || unsafe === undefined) return '';
        return unsafe
            .toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
    
    function getEnrollmentBadgeClass(count) {
        if (count === 0) return 'bg-gray-100 text-gray-800';
        if (count < 10) return 'bg-red-100 text-red-800';
        if (count < 30) return 'bg-yellow-100 text-yellow-800';
        return 'bg-green-100 text-green-800';
    }
    
    // Expose functions to global scope for HTML onclick handlers
    window.changePage = changePage;
});
