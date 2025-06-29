<?php
// Admin Dashboard
require_once "../config/constants.php";
require_once "../config/database.php";
require_once "../includes/auth.php";
require_once "../includes/functions.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $base_path . "index.php");
    exit();
}

// Redirect based on user role
if ($_SESSION['user_role'] === 'supervisor') {
    header("Location: " . $base_path . "analytics/supervisor_view.php");
    exit();
}

// Check if user is logged in and is an admin
require_role('admin');

// Get database connection first
$database = new Database();
$db = $database->getConnection();

// Handle view switching for admin
$current_view = isset($_GET['view']) ? $_GET['view'] : 'admin';
$valid_views = ['admin', 'supervisor', 'intern'];

if (!in_array($current_view, $valid_views)) {
    $current_view = 'admin';
}

// Handle specific user selection for supervisor/intern views
$selected_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$selected_user = null;

if ($selected_user_id && ($current_view === 'supervisor' || $current_view === 'intern')) {
    $user_stmt = $db->prepare("SELECT id, first_name, last_name, email, department, role FROM users WHERE id = :id AND role = :role");
    $user_stmt->execute([':id' => $selected_user_id, ':role' => $current_view]);
    $selected_user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    // If user not found or role doesn't match, reset to admin view
    if (!$selected_user) {
        $current_view = 'admin';
        $selected_user_id = null;
        $selected_user = null;
    }
}

// Store current view and selected user in session for navigation
$_SESSION['admin_current_view'] = $current_view;
$_SESSION['admin_selected_user_id'] = $selected_user_id;

// Get all users
$interns = get_users_by_role($db, 'intern');
$supervisors = get_users_by_role($db, 'supervisor');
$admins = get_users_by_role($db, 'admin');

// Get top performing interns
$top_interns = get_top_interns($db, 5);

// Get skill gap analysis
$skill_gaps = identify_skill_gaps($db);

// Take top 5 skill gaps
$top_skill_gaps = array_slice($skill_gaps, 0, 5);

// Calculate user stats
$total_users = count($interns) + count($supervisors) + count($admins);
$total_interns = count($interns);
$total_supervisors = count($supervisors);
$total_admins = count($admins);

// Get departments
$departments = [];
foreach (array_merge($interns, $supervisors) as $user) {
    if (!isset($departments[$user['department']])) {
        $departments[$user['department']] = 0;
    }
    $departments[$user['department']]++;
}
arsort($departments);

// Get attendance statistics for the last 7 days
$attendance_stats = [];
$status_counts = ['present' => 0, 'late' => 0, 'absent' => 0];

$sql = "SELECT date, status, COUNT(*) as count 
        FROM attendance 
        WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY date, status
        ORDER BY date";
$stmt = $db->prepare($sql);
$stmt->execute();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (!isset($attendance_stats[$row['date']])) {
        $attendance_stats[$row['date']] = [
            'present' => 0,
            'late' => 0,
            'absent' => 0
        ];
    }
    $attendance_stats[$row['date']][$row['status']] = $row['count'];
    $status_counts[$row['status']] += $row['count'];
}

// Define and sort users for the User Management table
$users = array_merge($interns, $supervisors, $admins);
usort($users, function($a, $b) {
    return strcmp($a['last_name'], $b['last_name']);
});

// Pagination settings
$users_per_page = 5;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$total_users = count($users);
$total_pages = ceil($total_users / $users_per_page);
$current_page = max(1, min($current_page, $total_pages));
$offset = ($current_page - 1) * $users_per_page;
$paginated_users = array_slice($users, $offset, $users_per_page);

// Handle direct admin view access
if (isset($_GET['admin_view']) && $_GET['admin_view'] == '1' && isset($_GET['user_id'])) {
    // Admin is directly viewing a supervisor's dashboard
    $viewed_user_id = (int)$_GET['user_id'];
    
    // Verify the user is actually a supervisor
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->prepare("SELECT id, first_name, last_name, email, department, role FROM users WHERE id = ? AND role = 'supervisor'");
    $stmt->execute([$viewed_user_id]);
    $viewed_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($viewed_user) {
        // Set up admin view session
        $_SESSION['admin_current_view'] = 'supervisor';
        $_SESSION['admin_selected_user_id'] = $viewed_user_id;
        
        // Redirect to clean URL
        header("Location: supervisor_dashboard.php");
        exit();
    }
}

// Clear admin view session when returning to admin dashboard
if (isset($_GET['clear_admin_view']) && $_GET['clear_admin_view'] == '1') {
    unset($_SESSION['admin_current_view']);
    unset($_SESSION['admin_selected_user_id']);
    header("Location: admin_dashboard.php");
    exit();
}

// Include header
require_once "../includes/header.php";
?>

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
    
    /* Dropdown positioning fixes */
    #supervisorDropdown, #internDropdown {
        position: absolute !important;
        top: 100% !important;
        right: 0 !important;
        z-index: 99999 !important;
        min-width: 16rem !important;
        margin-top: 0.5rem !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
        border-radius: 0.375rem !important;
        background-color: white !important;
        border: 1px solid #e5e7eb !important;
        display: none !important;
    }
    
    #supervisorDropdown.show, #internDropdown.show {
        display: block !important;
    }
    
    .dark #supervisorDropdown, .dark #internDropdown {
        background-color: #1f2937 !important;
        border-color: #374151 !important;
    }
    
    /* Ensure dropdown items are clickable */
    #supervisorDropdown a, #internDropdown a {
        display: block !important;
        padding: 0.5rem 1rem !important;
        text-decoration: none !important;
        color: #374151 !important;
        transition: background-color 0.15s ease !important;
        cursor: pointer !important;
    }
    
    .dark #supervisorDropdown a, .dark #internDropdown a {
        color: #d1d5db !important;
    }
    
    #supervisorDropdown a:hover, #internDropdown a:hover {
        background-color: #f3f4f6 !important;
    }
    
    .dark #supervisorDropdown a:hover, .dark #internDropdown a:hover {
        background-color: #374151 !important;
    }
    
    /* Button hover effects */
    button[onclick*="toggleSupervisorDropdown"]:hover,
    button[onclick*="toggleInternDropdown"]:hover {
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
    }
</style>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors duration-300">
    <!-- Top Navigation Bar -->
    <div class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Admin Dashboard</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- Switch View Buttons -->
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-600 dark:text-gray-300">Switch View:</span>
                        
                        <!-- Supervisor View with Dropdown -->
                        <div class="relative inline-block text-left">
                            <button type="button" onclick="toggleSupervisorDropdown()" class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 dark:focus:ring-offset-gray-800 transition-colors duration-200 cursor-pointer">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                Supervisor View
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div id="supervisorDropdown" class="hidden absolute right-0 mt-2 w-64 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 dark:ring-gray-600 z-50">
                                <div class="py-1" role="menu">
                                    <div class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-600">
                                        Select Supervisor:
                                    </div>
                                    <?php
                                    // Get all supervisors
                                    $supervisor_stmt = $db->prepare("SELECT id, first_name, last_name, email, department FROM users WHERE role = 'supervisor' ORDER BY first_name, last_name");
                                    $supervisor_stmt->execute();
                                    $supervisors = $supervisor_stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    if (empty($supervisors)): ?>
                                        <div class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                                            No supervisors found
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($supervisors as $supervisor): ?>
                                            <a href="../dashboards/supervisor_dashboard.php?admin_view=1&user_id=<?php echo $supervisor['id']; ?>" 
                                               class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150 cursor-pointer" 
                                               role="menuitem">
                                                <div class="flex items-center">
                                                    <div class="w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900 flex items-center justify-center mr-3">
                                                        <span class="text-purple-600 dark:text-purple-400 font-medium text-sm">
                                                            <?php echo strtoupper(substr($supervisor['first_name'], 0, 1) . substr($supervisor['last_name'], 0, 1)); ?>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <div class="font-medium text-gray-900 dark:text-white">
                                                            <?php echo htmlspecialchars($supervisor['first_name'] . ' ' . $supervisor['last_name']); ?>
                                                        </div>
                                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                                            <?php echo htmlspecialchars($supervisor['department']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Intern View with Dropdown -->
                        <div class="relative inline-block text-left">
                            <button type="button" onclick="toggleInternDropdown()" class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800 transition-colors duration-200 cursor-pointer">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                Intern View
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div id="internDropdown" class="hidden absolute right-0 mt-2 w-64 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 dark:ring-gray-600 z-50">
                                <div class="py-1" role="menu">
                                    <div class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-600">
                                        Select Intern:
                                    </div>
                                    <?php
                                    // Get all interns
                                    $intern_stmt = $db->prepare("SELECT id, first_name, last_name, email, department FROM users WHERE role = 'intern' ORDER BY first_name, last_name");
                                    $intern_stmt->execute();
                                    $interns = $intern_stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    if (empty($interns)): ?>
                                        <div class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                                            No interns found
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($interns as $intern): ?>
                                            <a href="../dashboards/intern_dashboard.php?admin_view=1&user_id=<?php echo $intern['id']; ?>" 
                                               class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150 cursor-pointer" 
                                               role="menuitem">
                                                <div class="flex items-center">
                                                    <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center mr-3">
                                                        <span class="text-blue-600 dark:text-blue-400 font-medium text-sm">
                                                            <?php echo strtoupper(substr($intern['first_name'], 0, 1) . substr($intern['last_name'], 0, 1)); ?>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <div class="font-medium text-gray-900 dark:text-white">
                                                            <?php echo htmlspecialchars($intern['first_name'] . ' ' . $intern['last_name']); ?>
                                                        </div>
                                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                                            <?php echo htmlspecialchars($intern['department']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <?php if (isset($_SESSION['admin_current_view']) && $_SESSION['admin_current_view'] !== 'admin'): ?>
                        <a href="admin_dashboard.php?clear_admin_view=1" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Back to Admin
                        </a>
                        <?php endif; ?>
                    </div>
        </div>
    </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (!isset($_SESSION['admin_current_view']) || $_SESSION['admin_current_view'] === 'admin'): ?>
        <!-- Admin View Content -->
        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border-l-4 border-blue-500 dark:border-blue-400 border border-gray-200 dark:border-gray-700 transition-colors duration-300">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-500 dark:text-blue-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
            </div>
                    <div class="ml-4">
                        <h2 class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Users</h2>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo $total_users; ?></p>
    </div>
    </div>
</div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border-l-4 border-green-500 dark:border-green-400 border border-gray-200 dark:border-gray-700 transition-colors duration-300">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 dark:bg-green-900 text-green-500 dark:text-green-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-sm font-medium text-gray-600 dark:text-gray-400">Interns</h2>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo $total_interns; ?></p>
                    </div>
    </div>
    </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border-l-4 border-purple-500 dark:border-purple-400 border border-gray-200 dark:border-gray-700 transition-colors duration-300">
                                    <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900 text-purple-500 dark:text-purple-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-sm font-medium text-gray-600 dark:text-gray-400">Supervisors</h2>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo $total_supervisors; ?></p>
                                        </div>
                                    </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border-l-4 border-red-500 dark:border-red-400 border border-gray-200 dark:border-gray-700 transition-colors duration-300">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 dark:bg-red-900 text-red-500 dark:text-red-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                            </div>
                    <div class="ml-4">
                        <h2 class="text-sm font-medium text-gray-600 dark:text-gray-400">Admins</h2>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo $total_admins; ?></p>
                    </div>
            </div>
    </div>
</div>

        <!-- Main Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column -->
            <div class="lg:col-span-2 space-y-8">
                <!-- User Management -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden border border-gray-200 dark:border-gray-700 transition-colors duration-300">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex justify-between items-center">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">User Management</h2>
                            <a href="../register.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800">
                                Add New User
                            </a>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200 dark:border-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600">Department</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600">Role</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <?php foreach ($paginated_users as $user): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap border-b border-gray-200 dark:border-gray-600">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <div class="h-10 w-10 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                                                        <span class="text-gray-500 dark:text-gray-300 font-medium">
                                                            <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                                        <?php echo htmlspecialchars($user['email']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-600">
                                            <?php echo htmlspecialchars($user['department']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap border-b border-gray-200 dark:border-gray-600">
                                            <?php 
                                                $role_colors = [
                                                    'intern' => 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200',
                                                    'supervisor' => 'bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200',
                                                    'admin' => 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200'
                                                ];
                                                $role_color = $role_colors[$user['role']] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200';
                                            ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $role_color; ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination -->
                    <div class="bg-white dark:bg-gray-800 px-4 py-3 flex items-center justify-between border-t border-gray-200 dark:border-gray-700 sm:px-6">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <?php if ($current_page > 1): ?>
                                <a href="?page=<?php echo $current_page - 1; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-150">
                                    Previous
                                </a>
                            <?php endif; ?>
                            <?php if ($current_page < $total_pages): ?>
                                <a href="?page=<?php echo $current_page + 1; ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-150">
                                    Next
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    Showing
                                    <span class="font-medium text-gray-900 dark:text-white"><?php echo $offset + 1; ?></span>
                                    to
                                    <span class="font-medium text-gray-900 dark:text-white"><?php echo min($offset + $users_per_page, $total_users); ?></span>
                                    of
                                    <span class="font-medium text-gray-900 dark:text-white"><?php echo $total_users; ?></span>
                                    results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <?php if ($current_page > 1): ?>
                                        <a href="?page=<?php echo $current_page - 1; ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-150">
                                            <span class="sr-only">Previous</span>
                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $start_page = max(1, $current_page - 2);
                                    $end_page = min($total_pages, $current_page + 2);
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++):
                                    ?>
                                        <a href="?page=<?php echo $i; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium <?php echo $i === $current_page ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600'; ?> transition-colors duration-150">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>
                                    
                                    <?php if ($current_page < $total_pages): ?>
                                        <a href="?page=<?php echo $current_page + 1; ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-150">
                                            <span class="sr-only">Next</span>
                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </a>
                                    <?php endif; ?>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Performing Interns -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden border border-gray-200 dark:border-gray-700 transition-colors duration-300">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Top Performing Interns</h2>
                    </div>
                    <div class="p-6">
                        <?php if (empty($top_interns)): ?>
                            <p class="text-gray-600 dark:text-gray-400">No intern performance data available yet.</p>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <thead>
                                        <tr>
                                            <th class="py-2 px-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Intern</th>
                                            <th class="py-2 px-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Department</th>
                                            <th class="py-2 px-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Advanced Skills</th>
                                            <th class="py-2 px-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Proficiency</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                <?php foreach ($top_interns as $intern): ?>
                                            <tr>
                                                <td class="py-3 px-4 border-b border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 font-medium"><?php echo htmlspecialchars($intern['first_name'] . ' ' . $intern['last_name']); ?></td>
                                                <td class="py-3 px-4 border-b border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($intern['department']); ?></td>
                                                <td class="py-3 px-4 border-b border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400"><?php echo $intern['advanced_skills'] . '/' . $intern['total_skills']; ?></td>
                                                <td class="py-3 px-4 border-b border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">
                                        <div class="flex items-center">
                                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                                                <div class="bg-green-500 h-2 rounded-full" style="width: <?php echo $intern['proficiency_percentage']; ?>%"></div>
                                            </div>
                                                        <span class="text-xs font-medium text-gray-600 dark:text-gray-300"><?php echo round($intern['proficiency_percentage']); ?>%</span>
                                        </div>
                                                </td>
                                            </tr>
                                <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-8">
                <!-- Quick Actions -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden border border-gray-200 dark:border-gray-700 transition-colors duration-300">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Quick Actions</h2>
                        <a href="../reports/generate_report.php" class="inline-flex items-center bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg shadow transition duration-200">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            View Report
                        </a>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 gap-4">
                            <a href="../register.php?role=intern" class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition duration-150">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Add New Intern</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Create intern account</p>
                                </div>
                            </a>
                            <a href="../register.php?role=supervisor" class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition duration-150">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Add New Supervisor</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Create supervisor account</p>
                                </div>
                            </a>
                            <a href="../departments/index.php" class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition duration-150">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-orange-500 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9M4 10v10a1 1 0 001 1h3a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h3a1 1 0 001-1V10" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Manage Departments</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Add, edit, or remove departments</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php elseif ($current_view === 'supervisor'): ?>
        <!-- Supervisor View Content -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700 transition-colors duration-300">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                    <?php if ($selected_user): ?>
                        Viewing as: <?php echo htmlspecialchars($selected_user['first_name'] . ' ' . $selected_user['last_name']); ?> (Supervisor)
                    <?php else: ?>
                        Supervisor View (Read-Only)
                    <?php endif; ?>
                </h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    <?php if ($selected_user): ?>
                        You are viewing the system as <?php echo htmlspecialchars($selected_user['first_name']); ?> would see it.
                    <?php else: ?>
                        You are viewing the system as a supervisor would see it.
                    <?php endif; ?>
                </p>
                
                <?php if ($selected_user): ?>
                <!-- Selected Supervisor Info -->
                <div class="bg-purple-50 dark:bg-purple-900 rounded-lg p-4 mb-6">
                    <div class="flex items-center justify-center space-x-4">
                        <div class="w-16 h-16 rounded-full bg-purple-100 dark:bg-purple-800 flex items-center justify-center">
                            <span class="text-purple-600 dark:text-purple-400 font-bold text-xl">
                                <?php echo strtoupper(substr($selected_user['first_name'], 0, 1) . substr($selected_user['last_name'], 0, 1)); ?>
                            </span>
                        </div>
                        <div class="text-left">
                            <h3 class="text-lg font-semibold text-purple-900 dark:text-purple-100">
                                <?php echo htmlspecialchars($selected_user['first_name'] . ' ' . $selected_user['last_name']); ?>
                            </h3>
                            <p class="text-purple-700 dark:text-purple-300"><?php echo htmlspecialchars($selected_user['department']); ?></p>
                            <p class="text-purple-600 dark:text-purple-400 text-sm"><?php echo htmlspecialchars($selected_user['email']); ?></p>
                    </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <a href="../dashboards/supervisor_dashboard.php" class="block p-6 bg-purple-50 dark:bg-purple-900 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-800 transition-colors duration-200">
                        <h3 class="text-lg font-semibold text-purple-900 dark:text-purple-100">Supervisor Dashboard</h3>
                        <p class="text-purple-700 dark:text-purple-300 text-sm">View intern management and task assignments</p>
                    </a>
                    <a href="../communication/index.php" class="block p-6 bg-green-50 dark:bg-green-900 rounded-lg hover:bg-green-100 dark:hover:bg-green-800 transition-colors duration-200">
                        <h3 class="text-lg font-semibold text-green-900 dark:text-green-100">Communication Center</h3>
                        <p class="text-green-700 dark:text-green-300 text-sm">Send messages and give task feedback</p>
                    </a>
                    <a href="../assign_task.php" class="block p-6 bg-blue-50 dark:bg-blue-900 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-800 transition-colors duration-200">
                        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100">Task Management</h3>
                        <p class="text-blue-700 dark:text-blue-300 text-sm">Assign and manage intern tasks</p>
                    </a>
                                        </div>
                                        </div>
                                    </div>
        <?php elseif ($current_view === 'intern'): ?>
        <!-- Intern View Content -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700 transition-colors duration-300">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                    <?php if ($selected_user): ?>
                        Viewing as: <?php echo htmlspecialchars($selected_user['first_name'] . ' ' . $selected_user['last_name']); ?> (Intern)
                    <?php else: ?>
                        Intern View (Read-Only)
                    <?php endif; ?>
                </h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    <?php if ($selected_user): ?>
                        You are viewing the system as <?php echo htmlspecialchars($selected_user['first_name']); ?> would see it.
                    <?php else: ?>
                        You are viewing the system as an intern would see it.
                    <?php endif; ?>
                </p>
                
                <?php if ($selected_user): ?>
                <!-- Selected Intern Info -->
                <div class="bg-blue-50 dark:bg-blue-900 rounded-lg p-4 mb-6">
                    <div class="flex items-center justify-center space-x-4">
                        <div class="w-16 h-16 rounded-full bg-blue-100 dark:bg-blue-800 flex items-center justify-center">
                            <span class="text-blue-600 dark:text-blue-400 font-bold text-xl">
                                <?php echo strtoupper(substr($selected_user['first_name'], 0, 1) . substr($selected_user['last_name'], 0, 1)); ?>
                            </span>
                        </div>
                        <div class="text-left">
                            <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100">
                                <?php echo htmlspecialchars($selected_user['first_name'] . ' ' . $selected_user['last_name']); ?>
                            </h3>
                            <p class="text-blue-700 dark:text-blue-300"><?php echo htmlspecialchars($selected_user['department']); ?></p>
                            <p class="text-blue-600 dark:text-blue-400 text-sm"><?php echo htmlspecialchars($selected_user['email']); ?></p>
                            </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <a href="../dashboards/intern_dashboard.php" class="block p-6 bg-blue-50 dark:bg-blue-900 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-800 transition-colors duration-200">
                        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100">Intern Dashboard</h3>
                        <p class="text-blue-700 dark:text-blue-300 text-sm">View assigned tasks and skill progress</p>
                    </a>
                    <a href="../communication/index.php" class="block p-6 bg-green-50 dark:bg-green-900 rounded-lg hover:bg-green-100 dark:hover:bg-green-800 transition-colors duration-200">
                        <h3 class="text-lg font-semibold text-green-900 dark:text-green-100">Communication Center</h3>
                        <p class="text-green-700 dark:text-green-300 text-sm">View messages and task feedback</p>
                    </a>
                    <a href="../my_tasks.php" class="block p-6 bg-orange-50 dark:bg-orange-900 rounded-lg hover:bg-orange-100 dark:hover:bg-orange-800 transition-colors duration-200">
                        <h3 class="text-lg font-semibold text-orange-900 dark:text-orange-100">My Tasks</h3>
                        <p class="text-orange-700 dark:text-orange-300 text-sm">View and update task progress</p>
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Initialize charts once and stop automatic reloading
var chartInitialized = false;

function initializeCharts() {
    if (chartInitialized) return;
    
    // Attendance Chart
    var attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
    var attendanceChart = new Chart(attendanceCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_keys($attendance_stats)); ?>,
            datasets: [
                {
                    label: 'Present',
                    data: <?php echo json_encode(array_map(function($day) { return $day['present']; }, $attendance_stats)); ?>,
                    backgroundColor: '#10B981'
                },
                {
                    label: 'Late',
                    data: <?php echo json_encode(array_map(function($day) { return $day['late']; }, $attendance_stats)); ?>,
                    backgroundColor: '#F59E0B'
                },
                {
                    label: 'Absent',
                    data: <?php echo json_encode(array_map(function($day) { return $day['absent']; }, $attendance_stats)); ?>,
                    backgroundColor: '#EF4444'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 0
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        color: document.documentElement.classList.contains('dark') ? '#D1D5DB' : '#374151'
                    },
                    grid: {
                        color: document.documentElement.classList.contains('dark') ? '#374151' : '#E5E7EB'
                    }
                },
                x: {
                    ticks: {
                        color: document.documentElement.classList.contains('dark') ? '#D1D5DB' : '#374151'
                    },
                    grid: {
                        color: document.documentElement.classList.contains('dark') ? '#374151' : '#E5E7EB'
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        color: document.documentElement.classList.contains('dark') ? '#D1D5DB' : '#374151',
                        font: {
                            size: 12
                        }
                    }
                }
            }
        }
    });

    // User Distribution Chart
    var ctx = document.getElementById('userDistributionChart').getContext('2d');
    var userDistributionChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Interns', 'Supervisors', 'Admins'],
            datasets: [{
                data: [
                    <?php echo $total_interns; ?>,
                    <?php echo $total_supervisors; ?>,
                    <?php echo $total_admins; ?>
                ],
                backgroundColor: [
                    '#3182CE', // blue-500
                    '#805AD5', // purple-500
                    '#E53E3E'  // red-500
                ],
                borderWidth: 1,
                borderColor: '#1F2937' // gray-800 for dark mode compatibility
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 0 // Disable animations to prevent constant redraws
            },
            legend: {
                position: 'right',
                labels: {
                    color: document.documentElement.classList.contains('dark') ? '#D1D5DB' : '#374151',
                    font: {
                        size: 12
                    }
                }
            },
            tooltips: {
                backgroundColor: document.documentElement.classList.contains('dark') ? '#374151' : '#FFFFFF',
                titleColor: document.documentElement.classList.contains('dark') ? '#F9FAFB' : '#111827',
                bodyColor: document.documentElement.classList.contains('dark') ? '#D1D5DB' : '#374151',
                borderColor: document.documentElement.classList.contains('dark') ? '#4B5563' : '#E5E7EB',
                borderWidth: 1,
                callbacks: {
                    label: function(tooltipItem, data) {
                        var dataset = data.datasets[tooltipItem.datasetIndex];
                        var total = dataset.data.reduce(function(previousValue, currentValue) {
                            return previousValue + currentValue;
                        });
                        var currentValue = dataset.data[tooltipItem.index];
                        var percentage = Math.floor(((currentValue/total) * 100)+0.5);
                        return data.labels[tooltipItem.index] + ': ' + currentValue + ' (' + percentage + '%)';
                    }
                }
            }
        }
    });
    
    chartInitialized = true;
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts only once
    initializeCharts();
    
    // Stop any potential auto-refresh or meta redirects
    var metaTags = document.getElementsByTagName('meta');
    for (var i = 0; i < metaTags.length; i++) {
        if (metaTags[i].getAttribute('http-equiv') === 'refresh') {
            metaTags[i].parentNode.removeChild(metaTags[i]);
        }
    }
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        const supervisorDropdown = document.getElementById('supervisorDropdown');
        const internDropdown = document.getElementById('internDropdown');
        
        if (supervisorDropdown && !supervisorDropdown.contains(e.target) && !e.target.closest('button[onclick*="toggleSupervisorDropdown"]')) {
            supervisorDropdown.classList.add('hidden');
            supervisorDropdown.classList.remove('show');
        }
        if (internDropdown && !internDropdown.contains(e.target) && !e.target.closest('button[onclick*="toggleInternDropdown"]')) {
            internDropdown.classList.add('hidden');
            internDropdown.classList.remove('show');
        }
    });
    
    // Close dropdowns when pressing Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const supervisorDropdown = document.getElementById('supervisorDropdown');
            const internDropdown = document.getElementById('internDropdown');
            
            if (supervisorDropdown) {
                supervisorDropdown.classList.add('hidden');
                supervisorDropdown.classList.remove('show');
            }
            if (internDropdown) {
                internDropdown.classList.add('hidden');
                internDropdown.classList.remove('show');
            }
        }
    });
});

// Simple dropdown toggle functions
function toggleSupervisorDropdown() {
    const dropdown = document.getElementById('supervisorDropdown');
    const internDropdown = document.getElementById('internDropdown');
    
    if (dropdown) {
        if (dropdown.classList.contains('hidden')) {
            dropdown.classList.remove('hidden');
            dropdown.classList.add('show');
        } else {
            dropdown.classList.add('hidden');
            dropdown.classList.remove('show');
        }
        console.log('Supervisor dropdown toggled');
    }
    
    // Close intern dropdown if open
    if (internDropdown) {
        internDropdown.classList.add('hidden');
        internDropdown.classList.remove('show');
    }
}

function toggleInternDropdown() {
    const dropdown = document.getElementById('internDropdown');
    const supervisorDropdown = document.getElementById('supervisorDropdown');
    
    if (dropdown) {
        if (dropdown.classList.contains('hidden')) {
            dropdown.classList.remove('hidden');
            dropdown.classList.add('show');
        } else {
            dropdown.classList.add('hidden');
            dropdown.classList.remove('show');
        }
        console.log('Intern dropdown toggled');
    }
    
    // Close supervisor dropdown if open
    if (supervisorDropdown) {
        supervisorDropdown.classList.add('hidden');
        supervisorDropdown.classList.remove('show');
    }
}
</script>

<?php
require_once "../includes/footer.php";
?>
