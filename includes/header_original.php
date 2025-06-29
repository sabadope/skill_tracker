<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the current script path
$script_path = $_SERVER['SCRIPT_NAME'];
$base_path = '';

// Calculate the number of directory levels from the root
$root_path = '/skill_tracker/';
$current_path = str_replace($root_path, '', $script_path);
$directory_levels = substr_count($current_path, '/');

// Set the base path based on directory levels
if ($directory_levels > 0) {
    $base_path = str_repeat('../', $directory_levels);
}

// Get current path for active state
$current_path = $_SERVER['PHP_SELF'];
?>
<!DOCTYPE html>
<html lang="en" class="transition-colors duration-300">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skill Development Tracker</title>
    <!-- Tailwind CSS from CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            300: '#cbd5e1',
                            400: '#94a3b8',
                            500: '#64748b',
                            600: '#475569',
                            700: '#334155',
                            800: '#1e293b',
                            900: '#0f172a',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Heroicons -->
    <script src="https://unpkg.com/@heroicons/v2/24/outline/esm/index.js"></script>
    <!-- Dark mode script -->
    <script src="<?php echo $base_path; ?>assets/js/darkmode.js"></script>
    <script>
        // Dropdown click functionality
        document.addEventListener('DOMContentLoaded', function() {
            const roleDropdown = document.getElementById('roleDropdown');
            const roleDropdownMenu = document.getElementById('roleDropdownMenu');
            
            if (roleDropdown && roleDropdownMenu) {
                roleDropdown.addEventListener('click', function(e) {
                    e.preventDefault();
                    roleDropdownMenu.classList.toggle('hidden');
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!roleDropdown.contains(e.target) && !roleDropdownMenu.contains(e.target)) {
                        roleDropdownMenu.classList.add('hidden');
                    }
                });
            }
        });
    </script>
    <style>
        /* Dark mode transitions */
        * {
            transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
        }
        
        /* Fix text alignment for navigation items */
        .nav-text {
            text-align: center;
            line-height: 1.2;
            vertical-align: middle;
            display: inline-block;
            font-weight: 500;
        }
        
        /* Perfect alignment for navigation links */
        .nav-link {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 2.5rem;
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        
        /* Consistent icon sizing */
        .nav-icon {
            width: 1rem;
            height: 1rem;
            flex-shrink: 0;
        }
        
        /* Perfect spacing between nav items */
        .nav-container {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex: 1;
            justify-content: center;
            margin-left: 1rem;
            margin-right: 1rem;
        }
        
        /* Mobile menu improvements */
        .mobile-nav-link {
            display: block;
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            font-size: 1rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        /* User info alignment */
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-shrink: 0;
        }
        
        /* Logo alignment */
        .logo-container {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-shrink: 0;
            justify-content: flex-start;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen transition-colors duration-300">
    <nav class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 fixed w-full z-50 transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-2 sm:px-4 lg:px-6">
            <div class="flex justify-between h-16">
                <!-- Logo and Brand -->
                <div class="logo-container flex-shrink-0">
                    <a href="<?php echo $base_path; ?>index.php" class="logo-container">
                        <svg class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-xl font-bold text-gray-900 dark:text-white">Smart Intern Management</span>
                    </a>
                </div>

                <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Desktop Navigation -->
                <div class="nav-container hidden md:flex">
                    <?php if ($_SESSION['user_role'] === 'intern'): ?>
                    <a href="<?php echo $base_path; ?>dashboards/intern_dashboard.php" 
                       class="nav-link text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 <?php echo (strpos($current_path, '/dashboards/intern_dashboard.php') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800 shadow' : ''; ?>">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span class="nav-text">Dashboard</span>
                    </a>
                    <a href="<?php echo $base_path; ?>skills/rename-skills_list.php" 
                       class="nav-link text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 <?php echo (strpos($current_path, '/skills/rename-skills_list.php') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800 shadow' : ''; ?>">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <span class="nav-text">My Skills</span>
                    </a>
                    <a href="<?php echo $base_path; ?>analytics/index.php" 
                       class="nav-link text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 <?php echo (strpos($current_path, '/analytics/') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800 shadow' : ''; ?>">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span class="nav-text">Analytics</span>
                    </a>
                    <a href="<?php echo $base_path; ?>communication/index.php" 
                       class="nav-link text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 <?php echo (strpos($current_path, '/communication/') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800 shadow' : ''; ?>">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                        <span class="nav-text">Communication</span>
                    </a>
                    <div class="relative group">
                        <a href="<?php echo $base_path; ?>attendance/index.php" 
                           class="nav-link text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 <?php echo (strpos($current_path, '/attendance/') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800 shadow' : ''; ?>">
                            <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="nav-text">Attendance</span>
                            <svg class="nav-icon ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </a>
                        <div class="absolute left-0 mt-2 w-48 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 dark:ring-gray-600 hidden group-hover:block">
                            <div class="py-1" role="menu" aria-orientation="vertical">
                                <a href="<?php echo $base_path; ?>Logs/admin_view.php" 
                                   class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md <?php echo (strpos($current_path, '/Logs/admin_view.php') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800' : ''; ?>" role="menuitem">
                                    Supervisor View
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php elseif ($_SESSION['user_role'] === 'supervisor'): ?>
                    <a href="<?php echo $base_path; ?>dashboards/supervisor_dashboard.php" 
                       class="flex items-center space-x-1 text-sm text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-200 px-3 py-2 rounded-md <?php echo (strpos($current_path, '/dashboards/supervisor_dashboard.php') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800 shadow' : ''; ?>">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Dashboard</span>
                    </a>
                    <a href="<?php echo $base_path; ?>skills/evaluate_skill.php" 
                       class="flex items-center space-x-1 text-sm text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-200 px-3 py-2 rounded-md <?php echo (strpos($current_path, '/skills/evaluate_skill.php') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800 shadow' : ''; ?>">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <span>Evaluate Interns</span>
                    </a>
                    <a href="<?php echo $base_path; ?>analytics/index.php" 
                       class="nav-link text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 <?php echo (strpos($current_path, '/analytics/') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800 shadow' : ''; ?>">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span class="nav-text">Analytics</span>
                    </a>
                    <a href="<?php echo $base_path; ?>communication/index.php" 
                       class="nav-link text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 <?php echo (strpos($current_path, '/communication/') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800 shadow' : ''; ?>">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                        <span class="nav-text">Communication</span>
                    </a>
                    <div class="relative">
                    <a href="<?php echo $base_path; ?>attendance/index.php" 
                           class="flex items-center space-x-1 text-sm text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-200 px-3 py-2 rounded-md <?php echo (strpos($current_path, '/attendance/') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800 shadow' : ''; ?>">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Attendance</span>
                        </a>
                    </div>
                    <?php elseif ($_SESSION['user_role'] === 'admin'): ?>
                    <a href="<?php echo $base_path; ?>dashboards/admin_dashboard.php" 
                       class="flex items-center space-x-1 text-sm text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-200 px-3 py-2 rounded-md <?php echo (strpos($current_path, '/dashboards/admin_dashboard.php') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800 shadow' : ''; ?>">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Dashboard</span>
                    </a>
                    <a href="<?php echo $base_path; ?>reports/generate_report.php" 
                       class="flex items-center space-x-1 text-sm text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-200 px-3 py-2 rounded-md <?php echo (strpos($current_path, '/reports/generate_report.php') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800 shadow' : ''; ?>">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Reports</span>
                    </a>
                    <a href="<?php echo $base_path; ?>analytics/index.php" 
                       class="nav-link text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 <?php echo (strpos($current_path, '/analytics/') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800 shadow' : ''; ?>">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span class="nav-text">Analytics</span>
                    </a>
                    <a href="<?php echo $base_path; ?>communication/index.php" 
                       class="nav-link text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 <?php echo (strpos($current_path, '/communication/') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800 shadow' : ''; ?>">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                        <span class="nav-text">Communication</span>
                    </a>
                    <div class="relative">
                        <a href="<?php echo $base_path; ?>attendance/index.php" 
                           class="flex items-center space-x-1 text-sm text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-200 px-3 py-2 rounded-md <?php echo (strpos($current_path, '/attendance/') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800 shadow' : ''; ?>">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Attendance</span>
                    </a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- User Menu and Dark Mode Toggle -->
                <div class="hidden md:flex items-center space-x-3 flex-shrink-0">
                    <!-- Dark Mode Toggle -->
                    <button id="darkModeToggle" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors duration-200" title="Toggle dark mode">
                        <!-- Sun icon (shown when dark mode is active) -->
                        <svg class="sun-icon h-5 w-5 text-yellow-500 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <!-- Moon icon (shown when light mode is active) -->
                        <svg class="moon-icon h-5 w-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>
                    
                    <div class="user-info">
                        <span class="text-sm text-gray-600 dark:text-gray-300"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                        <div class="relative">
                            <span id="roleDropdown" class="text-xs text-gray-400 dark:text-gray-500 cursor-pointer hover:text-blue-600 dark:hover:text-blue-400">(<?php echo ucfirst($_SESSION['user_role']); ?>)</span>
                            <div id="roleDropdownMenu" class="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 dark:ring-gray-600 hidden">
                                <div class="py-1" role="menu" aria-orientation="vertical">
                                    <a href="<?php echo $base_path; ?>register.php?role=supervisor" 
                                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md" role="menuitem">
                                        <div class="flex items-center">
                                            <svg class="h-4 w-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                            </svg>
                                            <span>Add Supervisor</span>
                                        </div>
                                    </a>
                                    <a href="<?php echo $base_path; ?>register.php?role=intern" 
                                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md" role="menuitem">
                                        <div class="flex items-center">
                                            <svg class="h-4 w-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            <span>Add Intern</span>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <a href="<?php echo $base_path; ?>logout.php" 
                       class="nav-link text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span class="nav-text">Logout</span>
                    </a>
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center space-x-2">
                    <!-- Dark Mode Toggle for Mobile -->
                    <button id="darkModeToggleMobile" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors duration-200" title="Toggle dark mode">
                        <!-- Sun icon (shown when dark mode is active) -->
                        <svg class="sun-icon h-5 w-5 text-yellow-500 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <!-- Moon icon (shown when light mode is active) -->
                        <svg class="moon-icon h-5 w-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>
                    
                    <button type="button" class="mobile-menu-button inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-300 hover:text-gray-500 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500">
                        <span class="sr-only">Open main menu</span>
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Mobile menu -->
        <div class="mobile-menu hidden md:hidden">
            <div class="px-2 pt-2 pb-3 space-y-1 bg-white dark:bg-gray-800">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['user_role'] === 'intern'): ?>
                    <a href="<?php echo $base_path; ?>dashboards/intern_dashboard.php" 
                       class="mobile-nav-link text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700 <?php echo (strpos($current_path, '/dashboards/intern_dashboard.php') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800' : ''; ?>">
                        Dashboard
                    </a>
                    <a href="<?php echo $base_path; ?>skills/rename-skills_list.php" 
                       class="mobile-nav-link text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700 <?php echo (strpos($current_path, '/skills/rename-skills_list.php') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800' : ''; ?>">
                        <span class="nav-text">My Skills</span>
                    </a>
                    <a href="<?php echo $base_path; ?>analytics/index.php" 
                       class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700 <?php echo (strpos($current_path, '/analytics/') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800' : ''; ?>">
                        Analytics
                    </a>
                    <a href="<?php echo $base_path; ?>communication/index.php" 
                       class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700 <?php echo (strpos($current_path, '/communication/') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800' : ''; ?>">
                        Communication
                    </a>
                    <a href="<?php echo $base_path; ?>Logs/index.php" 
                       class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700 <?php echo (strpos($current_path, '/Logs/index.php') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800' : ''; ?>" role="menuitem">
                        Daily Logs
                    </a>
                    <a href="<?php echo $base_path; ?>Logs/weekly_summary.php" 
                       class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700 <?php echo (strpos($current_path, '/Logs/weekly_summary.php') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800' : ''; ?>" role="menuitem">
                        Weekly Summary
                    </a>
                    <?php elseif ($_SESSION['user_role'] === 'supervisor'): ?>
                    <a href="<?php echo $base_path; ?>dashboards/supervisor_dashboard.php" 
                       class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700 <?php echo (strpos($current_path, '/dashboards/supervisor_dashboard.php') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800' : ''; ?>">
                        Dashboard
                    </a>
                    <a href="<?php echo $base_path; ?>skills/evaluate_skill.php" 
                       class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700 <?php echo (strpos($current_path, '/skills/evaluate_skill.php') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800' : ''; ?>">
                        Evaluate Interns
                    </a>
                    <a href="<?php echo $base_path; ?>analytics/index.php" 
                       class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700 <?php echo (strpos($current_path, '/analytics/') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800' : ''; ?>">
                        Analytics
                    </a>
                    <a href="<?php echo $base_path; ?>communication/index.php" 
                       class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700 <?php echo (strpos($current_path, '/communication/') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800' : ''; ?>">
                        Communication
                    </a>
                    <a href="<?php echo $base_path; ?>attendance/index.php" 
                       class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700 <?php echo (strpos($current_path, '/attendance/') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800' : ''; ?>">
                        Attendance
                    </a>
                    <a href="<?php echo $base_path; ?>Logs/admin_view.php" 
                       class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700 <?php echo (strpos($current_path, '/Logs/admin_view.php') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800' : ''; ?>" role="menuitem">
                        Admin View
                    </a>
                    <?php elseif ($_SESSION['user_role'] === 'admin'): ?>
                    <a href="<?php echo $base_path; ?>attendance/index.php" 
                       class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700 <?php echo (strpos($current_path, '/attendance/') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800' : ''; ?>">
                        Attendance
                    </a>
                    <?php endif; ?>
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 pb-3">
                        <div class="px-4">
                            <div class="text-base font-medium text-gray-800 dark:text-white"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">(<?php echo ucfirst($_SESSION['user_role']); ?>)</div>
                        </div>
                        <div class="mt-3">
                            <a href="<?php echo $base_path; ?>logout.php" 
                               class="block px-4 py-2 text-base font-medium text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                Logout
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Add padding to account for fixed header -->
    <div class="pt-16">
    
    <!-- Display flash messages if any -->
    <?php if (isset($_SESSION['success'])): ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        <div class="bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline"><?php echo $_SESSION['success']; ?></span>
            <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                <svg class="fill-current h-6 w-6 text-green-500 dark:text-green-400" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                    <title>Close</title>
                    <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                </svg>
            </span>
        </div>
    </div>
    <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        <div class="bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline"><?php echo $_SESSION['error']; ?></span>
            <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                <svg class="fill-current h-6 w-6 text-red-500 dark:text-red-400" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                    <title>Close</title>
                    <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                </svg>
            </span>
        </div>
    </div>
    <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
</div>
