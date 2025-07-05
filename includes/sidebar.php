<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="bg-gradient-to-b from-blue-800 to-blue-900 text-white w-64 px-4 py-6 flex flex-col h-full">
    <div class="flex items-center justify-center mb-8">
        <i class="fas fa-graduation-cap text-3xl mr-2"></i>
        <h1 class="text-xl font-bold">Moodle Analytics</h1>
    </div>
    
    <nav class="flex-1">
        <ul class="space-y-2">
            <li>
                <a href="index.php" class="flex items-center px-4 py-3 rounded-lg transition-colors <?php echo $current_page === 'index.php' ? 'bg-blue-700 text-white' : 'text-blue-100 hover:bg-blue-700/50'; ?>">
                    <i class="fas fa-tachometer-alt w-6 text-center mr-3"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="courses.php" class="flex items-center px-4 py-3 rounded-lg transition-colors <?php echo $current_page === 'courses.php' ? 'bg-blue-700 text-white' : 'text-blue-100 hover:bg-blue-700/50'; ?>">
                    <i class="fas fa-book w-6 text-center mr-3"></i>
                    <span>All Courses</span>
                    <span class="ml-auto bg-blue-600 text-white text-xs font-semibold px-2 py-1 rounded-full">
                        <?php 
                        if (!function_exists('get_all_courses_with_enrollments')) {
                            require_once __DIR__ . '/../api_functions.php';
                        }
                        $all_courses = get_all_courses_with_enrollments();
                        echo is_array($all_courses) ? count($all_courses) : '0';
                        ?>
                    </span>
                </a>
            </li>
            <li>
                <a href="#" class="flex items-center px-4 py-3 rounded-lg transition-colors text-blue-100 hover:bg-blue-700/50">
                    <i class="fas fa-users w-6 text-center mr-3"></i>
                    <span>Users</span>
                </a>
            </li>
            <li>
                <a href="#" class="flex items-center px-4 py-3 rounded-lg transition-colors text-blue-100 hover:bg-blue-700/50">
                    <i class="fas fa-chart-bar w-6 text-center mr-3"></i>
                    <span>Reports</span>
                </a>
            </li>
            <li>
                <a href="#" class="flex items-center px-4 py-3 rounded-lg transition-colors text-blue-100 hover:bg-blue-700/50">
                    <i class="fas fa-cog w-6 text-center mr-3"></i>
                    <span>Settings</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="mt-auto pt-4 border-t border-blue-700">
        <div class="flex items-center px-4 py-3 text-blue-200">
            <div class="w-10 h-10 rounded-full bg-blue-700 flex items-center justify-center mr-3">
                <i class="fas fa-user"></i>
            </div>
            <div>
                <p class="font-medium">Admin User</p>
                <p class="text-xs text-blue-300">Administrator</p>
            </div>
        </div>
        <a href="#" class="block mt-4 px-4 py-2 text-sm text-center text-blue-200 hover:bg-blue-700/50 rounded-lg transition-colors">
            <i class="fas fa-sign-out-alt mr-2"></i>Sign Out
        </a>
    </div>
</div>
