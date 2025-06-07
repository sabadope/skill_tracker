<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Determine the base path for links
$base_path = '';
$current_path = $_SERVER['PHP_SELF'];

// Check which directory we're in and set the appropriate base path
if (strpos($current_path, '/dashboards/') !== false) {
    $base_path = '../';
} else if (strpos($current_path, '/skills/') !== false) {
    $base_path = '../';
} else if (strpos($current_path, '/reports/') !== false) {
    $base_path = '../';
} else if (strpos($current_path, '/communication/') !== false) {
    $base_path = '../';
}

// Get current page for navigation highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skill Development Tracker</title>
    <!-- Tailwind CSS from CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-gray-800 text-white shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex justify-between">
                <div class="flex space-x-7">
                    <div>
                        <a href="<?php echo $base_path; ?>index.php" class="flex items-center py-4 px-2">
                            <span class="font-semibold text-white text-lg">Smart Intern Management</span>
                        </a>
                    </div>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="hidden md:flex items-center space-x-1">
                        <?php if ($_SESSION['user_role'] === 'intern'): ?>
                        <a href="<?php echo $base_path; ?>dashboards/intern_dashboard.php" 
                           class="py-4 px-2 <?php echo (strpos($current_path, '/dashboards/intern_dashboard.php') !== false) ? 'text-blue-500 border-b-2 border-blue-500' : 'text-gray-300 hover:text-blue-500 hover:border-b-2 hover:border-blue-500'; ?>">
                            Dashboard
                        </a>
                        <a href="<?php echo $base_path; ?>skills/skills_list.php" 
                           class="py-4 px-2 <?php echo (strpos($current_path, '/skills/skills_list.php') !== false) ? 'text-blue-500 border-b-2 border-blue-500' : 'text-gray-300 hover:text-blue-500 hover:border-b-2 hover:border-blue-500'; ?>">
                            My Skills
                        </a>
                        <a href="<?php echo $base_path; ?>communication/index.php" 
                           class="py-4 px-2 <?php echo (strpos($current_path, '/communication/') !== false) ? 'text-blue-500 border-b-2 border-blue-500' : 'text-gray-300 hover:text-blue-500 hover:border-b-2 hover:border-blue-500'; ?>">
                            Communication
                        </a>
                        <?php elseif ($_SESSION['user_role'] === 'supervisor'): ?>
                        <a href="<?php echo $base_path; ?>dashboards/supervisor_dashboard.php" 
                           class="py-4 px-2 <?php echo (strpos($current_path, '/dashboards/supervisor_dashboard.php') !== false) ? 'text-blue-500 border-b-2 border-blue-500' : 'text-gray-300 hover:text-blue-500 hover:border-b-2 hover:border-blue-500'; ?>">
                            Dashboard
                        </a>
                        <a href="<?php echo $base_path; ?>skills/evaluate_skill.php" 
                           class="py-4 px-2 <?php echo (strpos($current_path, '/skills/evaluate_skill.php') !== false) ? 'text-blue-500 border-b-2 border-blue-500' : 'text-gray-300 hover:text-blue-500 hover:border-b-2 hover:border-blue-500'; ?>">
                            Evaluate Interns
                        </a>
                        <a href="<?php echo $base_path; ?>communication/index.php" 
                           class="py-4 px-2 <?php echo (strpos($current_path, '/communication/') !== false) ? 'text-blue-500 border-b-2 border-blue-500' : 'text-gray-300 hover:text-blue-500 hover:border-b-2 hover:border-blue-500'; ?>">
                            Communication
                        </a>
                        <?php elseif ($_SESSION['user_role'] === 'admin'): ?>
                        <a href="<?php echo $base_path; ?>dashboards/admin_dashboard.php" 
                           class="py-4 px-2 <?php echo (strpos($current_path, '/dashboards/admin_dashboard.php') !== false) ? 'text-blue-500 border-b-2 border-blue-500' : 'text-gray-300 hover:text-blue-500 hover:border-b-2 hover:border-blue-500'; ?>">
                            Dashboard
                        </a>
                        <a href="<?php echo $base_path; ?>reports/generate_report.php" 
                           class="py-4 px-2 <?php echo (strpos($current_path, '/reports/generate_report.php') !== false) ? 'text-blue-500 border-b-2 border-blue-500' : 'text-gray-300 hover:text-blue-500 hover:border-b-2 hover:border-blue-500'; ?>">
                            Reports
                        </a>
                        <a href="<?php echo $base_path; ?>register.php" 
                           class="py-4 px-2 <?php echo (strpos($current_path, '/register.php') !== false) ? 'text-blue-500 border-b-2 border-blue-500' : 'text-gray-300 hover:text-blue-500 hover:border-b-2 hover:border-blue-500'; ?>">
                            Add User
                        </a>
                        <a href="<?php echo $base_path; ?>communication/index.php" 
                           class="py-4 px-2 <?php echo (strpos($current_path, '/communication/') !== false) ? 'text-blue-500 border-b-2 border-blue-500' : 'text-gray-300 hover:text-blue-500 hover:border-b-2 hover:border-blue-500'; ?>">
                            Communication
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="hidden md:flex items-center space-x-3">
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm"><?php echo htmlspecialchars($_SESSION['user_name']); ?> (<?php echo ucfirst($_SESSION['user_role']); ?>)</span>
                        <a href="<?php echo $base_path; ?>logout.php" class="py-2 px-3 bg-red-500 hover:bg-red-600 text-white rounded transition duration-300">Logout</a>
                    </div>
                    <?php else: ?>
                    <a href="<?php echo $base_path; ?>index.php" class="py-2 px-3 bg-blue-500 hover:bg-blue-600 text-white rounded transition duration-300">Home</a>
                    <?php endif; ?>
                </div>
                
                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button class="mobile-menu-button outline-none">
                        <svg class="w-6 h-6 text-white" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                            <path d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div class="mobile-menu hidden md:hidden">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['user_role'] === 'intern'): ?>
                <a href="<?php echo $base_path; ?>dashboards/intern_dashboard.php" class="block py-2 px-4 text-sm hover:bg-gray-700">Dashboard</a>
                <a href="<?php echo $base_path; ?>skills/skills_list.php" class="block py-2 px-4 text-sm hover:bg-gray-700">My Skills</a>
                <a href="<?php echo $base_path; ?>communication/index.php" class="block py-2 px-4 text-sm hover:bg-gray-700">Communication</a>
                <?php elseif ($_SESSION['user_role'] === 'supervisor'): ?>
                <a href="<?php echo $base_path; ?>dashboards/supervisor_dashboard.php" class="block py-2 px-4 text-sm hover:bg-gray-700">Dashboard</a>
                <a href="<?php echo $base_path; ?>skills/evaluate_skill.php" class="block py-2 px-4 text-sm hover:bg-gray-700">Evaluate Interns</a>
                <a href="<?php echo $base_path; ?>communication/index.php" class="block py-2 px-4 text-sm hover:bg-gray-700">Communication</a>
                <?php elseif ($_SESSION['user_role'] === 'admin'): ?>
                <a href="<?php echo $base_path; ?>dashboards/admin_dashboard.php" class="block py-2 px-4 text-sm hover:bg-gray-700">Dashboard</a>
                <a href="<?php echo $base_path; ?>reports/generate_report.php" class="block py-2 px-4 text-sm hover:bg-gray-700">Reports</a>
                <a href="<?php echo $base_path; ?>register.php" class="block py-2 px-4 text-sm hover:bg-gray-700">Add User</a>
                <a href="<?php echo $base_path; ?>communication/index.php" class="block py-2 px-4 text-sm hover:bg-gray-700">Communication</a>
                <?php endif; ?>
                <a href="<?php echo $base_path; ?>logout.php" class="block py-2 px-4 text-sm hover:bg-gray-700">Logout</a>
            <?php else: ?>
                <a href="<?php echo $base_path; ?>login.php" class="block py-2 px-4 text-sm hover:bg-gray-700">Login</a>
            <?php endif; ?>
        </div>
    </nav>
    
    <!-- Display flash messages if any -->
    <?php if (isset($_SESSION['success'])): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 mx-4 mt-4" role="alert">
        <span class="block sm:inline"><?php echo $_SESSION['success']; ?></span>
        <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
            <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
        </span>
    </div>
    <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 mx-4 mt-4" role="alert">
        <span class="block sm:inline"><?php echo $_SESSION['error']; ?></span>
        <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
            <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
        </span>
    </div>
    <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <div class="container mx-auto px-4 py-6">
