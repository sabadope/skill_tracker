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

// Fetch profile_pic for header display
$profilePic = null;
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/database.php';
    $db = (new Database())->getConnection();
    $stmt = $db->prepare('SELECT profile_pic FROM users WHERE id = :id');
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && !empty($row['profile_pic'])) {
        $profilePic = $row['profile_pic'];
        if ($profilePic[0] !== '/') {
            $profilePic = '/' . $profilePic;
        }
    }
}
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
            const roleDropdownTriggers = document.querySelectorAll('.roleDropdownTrigger');
            const roleDropdownMenu = document.getElementById('roleDropdownMenu');
            const profileChevron = document.getElementById('profileChevron');
            
            if (roleDropdownTriggers.length && roleDropdownMenu && profileChevron) {
                roleDropdownTriggers.forEach(function(trigger) {
                    trigger.addEventListener('click', function(e) {
                        e.preventDefault();
                        roleDropdownMenu.classList.toggle('hidden');
                        profileChevron.classList.toggle('rotate-180');
                    });
                });
                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    let clickedTrigger = false;
                    roleDropdownTriggers.forEach(function(trigger) {
                        if (trigger.contains(e.target)) clickedTrigger = true;
                    });
                    if (!clickedTrigger && !roleDropdownMenu.contains(e.target)) {
                        roleDropdownMenu.classList.add('hidden');
                        profileChevron.classList.remove('rotate-180');
                    }
                });
            }
        });
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.nav-link').forEach(function(navLink) {
            if (navLink.textContent.trim().includes('Attendance') || navLink.textContent.trim().includes('Activities')) {
                let dropdown = navLink.parentElement.querySelector('.custom-dropdown');
                if (!dropdown) return;

                let hideTimeout;

                function showDropdown() {
                    clearTimeout(hideTimeout);
                    dropdown.classList.remove('hidden');
                }

                function hideDropdownWithDelay() {
                    hideTimeout = setTimeout(function() {
                        dropdown.classList.add('hidden');
                    }, 500); // 3 seconds
                }

                navLink.addEventListener('mouseenter', showDropdown);
                navLink.addEventListener('mouseleave', hideDropdownWithDelay);

                dropdown.addEventListener('mouseenter', function() {
                    clearTimeout(hideTimeout);
                });
                dropdown.addEventListener('mouseleave', hideDropdownWithDelay);
            }
        });
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
        .rotate-180 { transform: rotate(180deg); }
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
                    <?php 
                    // Determine which role's navigation to show
                    $display_role = $_SESSION['user_role'];
                    if ($_SESSION['user_role'] === 'admin' && isset($_SESSION['admin_current_view']) && $_SESSION['admin_current_view'] !== 'admin') {
                        $display_role = $_SESSION['admin_current_view'];
                    }
                    
                    if ($display_role === 'intern'): ?>
                    <a href="<?php echo $base_path; ?>dashboards/intern_dashboard.php" 
                       class="nav-link text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 <?php echo (strpos($current_path, '/dashboards/intern_dashboard.php') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800 shadow' : ''; ?>">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                        <span class="nav-text">Dashboard</span>
                    </a>
                    <a href="<?php echo $base_path; ?>skills/rename-skills_list.php" 
                       class="nav-link text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 <?php echo (strpos($current_path, '/skills/rename-skills_list.php') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800 shadow' : ''; ?>">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        <span class="nav-text">My Skills</span>
                    </a>
                    <a href="<?php echo $base_path; ?>my_tasks.php"
                       class="nav-link text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 <?php echo (strpos($current_path, '/my_tasks.php') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800 shadow' : ''; ?>">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span class="nav-text">My Tasks</span>
                    </a>
                    <div class="relative group">
                        <a href="#" class="nav-link text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 flex items-center">
                            <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            <span class="nav-text">Activities</span>
                            <svg class="nav-icon ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </a>
                        <div class="absolute left-0 mt-2 w-48 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 dark:ring-gray-600 hidden custom-dropdown">
                            <div class="py-1" role="menu" aria-orientation="vertical">
                                <a href="<?php echo $base_path; ?>analytics/index.php" 
                                   class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md <?php echo (strpos($current_path, '/analytics/') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold' : ''; ?>" role="menuitem">
                                    Analytics
                                </a>
                                <a href="<?php echo $base_path; ?>attendance/index.php" 
                                   class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md <?php echo (strpos($current_path, '/attendance/') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold' : ''; ?>" role="menuitem">
                                    Attendance
                                </a>
                                <a href="<?php echo $base_path; ?>communication/index.php" 
                                   class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md <?php echo (strpos($current_path, '/communication/') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold' : ''; ?>" role="menuitem">
                                    Communication
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php elseif ($display_role === 'supervisor'): ?>
                    <a href="<?php echo $base_path; ?>dashboards/supervisor_dashboard.php" 
                       class="flex items-center space-x-1 text-sm text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-200 px-3 py-2 rounded-md <?php echo (strpos($current_path, '/dashboards/supervisor_dashboard.php') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800 shadow' : ''; ?>">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Dashboard</span>
                    </a>
                    <a href="<?php echo $base_path; ?>skills/evaluate_skill.php" 
                       class="nav-link text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 <?php echo (strpos($current_path, '/skills/evaluate_skill.php') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800 shadow' : ''; ?>">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <span class="nav-text">Evaluate Interns</span>
                    </a>
                    <a href="<?php echo $base_path; ?>assign_task.php" 
                       class="nav-link text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 <?php echo (strpos($current_path, '/assign_task.php') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold ring-2 ring-blue-300 dark:ring-blue-800 shadow' : ''; ?>">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span class="nav-text">Assign Task</span>
                    </a>
                    
                    <div class="relative group">
                        <a href="#" class="nav-link text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">
                            <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            <span class="nav-text">Activities</span>
                            <svg class="nav-icon ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </a>
                        <div class="absolute left-0 mt-2 w-48 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 dark:ring-gray-600 hidden custom-dropdown">
                            <div class="py-1" role="menu" aria-orientation="vertical">
                                <a href="<?php echo $base_path; ?>analytics/index.php" 
                                   class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md <?php echo (strpos($current_path, '/analytics/') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold' : ''; ?>" role="menuitem">
                                    Analytics
                                </a>
                                <a href="<?php echo $base_path; ?>attendance/index.php" 
                                   class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md <?php echo (strpos($current_path, '/attendance/') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold' : ''; ?>" role="menuitem">
                                    Attendance
                                </a>
                                <a href="<?php echo $base_path; ?>communication/index.php" 
                                   class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md <?php echo (strpos($current_path, '/communication/') !== false) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-semibold' : ''; ?>" role="menuitem">
                                    Communication
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php elseif ($display_role === 'admin'): ?>
                    <?php 
                    // Get current view for admin navigation
                    $admin_view = isset($_SESSION['admin_current_view']) ? $_SESSION['admin_current_view'] : 'admin';
                    $admin_user_id = isset($_SESSION['admin_selected_user_id']) ? $_SESSION['admin_selected_user_id'] : null;
                    $view_param = $admin_view !== 'admin' ? "?view=$admin_view" : '';
                    $view_param .= $admin_user_id ? ($view_param ? "&user_id=$admin_user_id" : "?user_id=$admin_user_id") : '';
                    ?>
                    <a href="<?php echo $base_path; ?>dashboards/admin_dashboard.php<?php echo $view_param; ?>" 
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
                        <div class="relative flex items-center gap-2">
                            <?php 
                            // Determine which user's info to display
                            $display_user_name = $_SESSION['user_name'];
                            $display_user_initial = strtoupper(substr($_SESSION['user_name'], 0, 1));
                            
                            // If admin is in view mode, show the viewed user's info
                            if ($_SESSION['user_role'] === 'admin' && isset($_SESSION['admin_current_view']) && $_SESSION['admin_current_view'] !== 'admin' && isset($_SESSION['admin_selected_user_id'])) {
                                require_once __DIR__ . '/../config/database.php';
                                $db = (new Database())->getConnection();
                                $stmt = $db->prepare('SELECT first_name, last_name FROM users WHERE id = :id');
                                $stmt->execute([':id' => $_SESSION['admin_selected_user_id']]);
                                $viewed_user = $stmt->fetch(PDO::FETCH_ASSOC);
                                if ($viewed_user) {
                                    $display_user_name = $viewed_user['first_name'] . ' ' . $viewed_user['last_name'];
                                    $display_user_initial = strtoupper(substr($viewed_user['first_name'], 0, 1));
                                }
                            }
                            ?>
                            <!-- Profile Circle with Initial or Image -->
                            <?php if (!empty($profilePic)): ?>
                                <div class="roleDropdownTrigger flex items-center justify-center w-8 h-8 rounded-full bg-blue-500 dark:bg-blue-400 text-white font-bold text-lg select-none cursor-pointer hover:ring-2 hover:ring-blue-300 dark:hover:ring-blue-600 transition overflow-hidden" title="Account menu">
                                    <img src="<?php echo htmlspecialchars($profilePic) . '?t=' . time(); ?>" alt="Profile" class="w-full h-full object-cover" />
                                </div>
                            <?php else: ?>
                                <div class="roleDropdownTrigger flex items-center justify-center w-8 h-8 rounded-full bg-blue-500 dark:bg-blue-400 text-white font-bold text-lg select-none cursor-pointer hover:ring-2 hover:ring-blue-300 dark:hover:ring-blue-600 transition" title="Account menu">
                                    <?php echo $display_user_initial; ?>
                                </div>
                            <?php endif; ?>
                            <span class="roleDropdownTrigger text-sm text-gray-600 dark:text-gray-300 cursor-pointer hover:text-blue-600 dark:hover:text-blue-400"><?php echo htmlspecialchars($display_user_name); ?></span>
                            <svg id="profileChevron" class="roleDropdownTrigger h-4 w-4 ml-1 text-gray-400 dark:text-gray-300 inline-block cursor-pointer transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            <div id="roleDropdownMenu" class="absolute right-0 top-full w-48 min-w-[12rem] rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 dark:ring-gray-600 z-50 hidden">
                                <div class="py-1" role="menu" aria-orientation="vertical">
                                    <?php if ($_SESSION['user_role'] === 'admin' || (isset($_SESSION['can_access_supervisor']) && $_SESSION['can_access_supervisor'])): ?>
                                        <a href="<?php echo $base_path; ?>register.php?role=supervisor" 
                                           class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md" role="menuitem">
                                            <div class="flex items-center">
                                                <svg class="h-4 w-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                                </svg>
                                                <span>Add Supervisor</span>
                                            </div>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'supervisor' || (isset($_SESSION['can_access_supervisor']) && $_SESSION['can_access_supervisor'])): ?>
                                        <a href="<?php echo $base_path; ?>register.php?role=intern" 
                                           class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md" role="menuitem">
                                            <div class="flex items-center">
                                                <svg class="h-4 w-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                                <span>Add Intern</span>
                                            </div>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                        <a href="<?php echo $base_path; ?>departments/index.php" 
                                           class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md" role="menuitem">
                                            <div class="flex items-center">
                                                <svg class="h-4 w-4 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9M4 10v10a1 1 0 001 1h3a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h3a1 1 0 001-1V10" />
                                                </svg>
                                                <span>Manage Departments</span>
                                            </div>
                                        </a>
                                    <?php endif; ?>
                                    <div class="border-t border-gray-200 dark:border-gray-600 my-1"></div>
                                    <a href="<?php echo $base_path; ?>profile.php" 
                                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md" role="menuitem">
                                        <div class="flex items-center">
                                            <svg class="h-4 w-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m0 14v1m8.485-8.485l-.707.707M4.222 19.778l-.707-.707M19.778 19.778l-.707-.707M4.222 4.222l-.707.707M21 12h-1M4 12H3m16.485-4.485l-.707-.707M7.05 16.95l-.707-.707" />
                                            </svg>
                                            <span>Edit Profile</span>
                                        </div>
                                    </a>
                                    <a href="<?php echo $base_path; ?>logout.php" 
                                       class="block px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md" role="menuitem">
                                        <div class="flex items-center">
                                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                            </svg>
                                            <span>Logout</span>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <span class="text-xs text-gray-400 dark:text-gray-500">(<?php echo ucfirst($display_role); ?>)</span>
                    </div>
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
                    <?php if ($display_role === 'intern'): ?>
                    <a href="<?php echo $base_path; ?>dashboards/intern_dashboard.php" class="mobile-nav-link text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Dashboard</a>
                    <a href="<?php echo $base_path; ?>skills/rename-skills_list.php" class="mobile-nav-link text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">My Skills</a>
                    <a href="<?php echo $base_path; ?>my_tasks.php" class="mobile-nav-link text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">My Tasks</a>
                    <a href="<?php echo $base_path; ?>Logs/index.php" class="mobile-nav-link text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Activities</a>
                    <?php elseif ($display_role === 'supervisor'): ?>
                    <a href="<?php echo $base_path; ?>dashboards/supervisor_dashboard.php" 
                       class="mobile-nav-link text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Dashboard</a>
                    <a href="<?php echo $base_path; ?>skills/evaluate_skill.php" 
                       class="mobile-nav-link text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Evaluate Interns</a>
                    <a href="<?php echo $base_path; ?>assign_task.php" 
                       class="mobile-nav-link text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Assign Task</a>
                    <a href="<?php echo $base_path; ?>analytics/index.php" 
                       class="mobile-nav-link text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Analytics</a>
                    <a href="<?php echo $base_path; ?>attendance/index.php" 
                       class="mobile-nav-link text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Attendance</a>
                    <a href="<?php echo $base_path; ?>communication/index.php" 
                       class="mobile-nav-link text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Communication</a>
                    <?php elseif ($display_role === 'admin'): ?>
                    <a href="<?php echo $base_path; ?>dashboards/admin_dashboard.php" 
                       class="mobile-nav-link text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Dashboard</a>
                    <a href="<?php echo $base_path; ?>reports/generate_report.php" 
                       class="mobile-nav-link text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Reports</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?php echo $base_path; ?>login.php" class="mobile-nav-link text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Login</a>
                    <a href="<?php echo $base_path; ?>register.php" class="mobile-nav-link text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Register</a>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    const darkModeToggle = document.getElementById('darkModeToggle');
    const darkModeToggleMobile = document.getElementById('darkModeToggleMobile');

    mobileMenuButton.addEventListener('click', function() {
        mobileMenu.classList.toggle('hidden');
    });

    function setDarkMode(isDark) {
        if (isDark) {
            document.documentElement.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        } else {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        }
        updateIcons(isDark);
            }

    function updateIcons(isDark) {
        document.querySelectorAll('.sun-icon').forEach(icon => icon.classList.toggle('hidden', !isDark));
        document.querySelectorAll('.moon-icon').forEach(icon => icon.classList.toggle('hidden', isDark));
    }

    darkModeToggle.addEventListener('click', () => {
        setDarkMode(!document.documentElement.classList.contains('dark'));
    });

    darkModeToggleMobile.addEventListener('click', () => {
        setDarkMode(!document.documentElement.classList.contains('dark'));
            });

    // Set initial theme based on localStorage
    const initialTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    setDarkMode(initialTheme === 'dark' || (initialTheme === null && prefersDark));
});
</script>
</body>
</html>
