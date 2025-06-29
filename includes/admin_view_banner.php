<?php
// Admin View Mode Banner Component
// This banner should be included on ALL pages when admin is in view mode

// Check if admin is in view mode
$is_admin_view_mode = is_admin_in_view_mode();
$admin_view_mode = get_admin_view_mode();
$selected_user_id = get_admin_selected_user_id();

if ($is_admin_view_mode):
    // Get the viewed user's name for display
    $viewed_user_name = '';
    if ($selected_user_id) {
        require_once __DIR__ . '/../config/database.php';
        $db = (new Database())->getConnection();
        $stmt = $db->prepare('SELECT first_name, last_name FROM users WHERE id = :id');
        $stmt->execute([':id' => $selected_user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $viewed_user_name = $user['first_name'] . ' ' . $user['last_name'];
        }
    }
    
    $role_display = ucfirst($admin_view_mode);
    $user_display = $viewed_user_name ? " ($viewed_user_name)" : "";
?>
<!-- Admin View Mode Banner - Persistent across all pages -->
<div class="bg-yellow-50 dark:bg-yellow-900 border-l-4 border-yellow-400 dark:border-yellow-500 p-4 mb-8 rounded-lg sticky top-16 z-40 shadow-lg">
    <div class="flex items-center justify-between">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400 dark:text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                    Admin View Mode - Read Only
                </h3>
                <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                    You are viewing this page as a <strong><?php echo $role_display; ?></strong><?php echo $user_display; ?>. All interactions are disabled.
                </p>
            </div>
        </div>
        <div class="flex-shrink-0">
            <a href="<?php echo $base_path ?? ''; ?>dashboards/admin_dashboard.php?view=admin" 
               class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-yellow-800 dark:text-yellow-200 bg-yellow-100 dark:bg-yellow-800 hover:bg-yellow-200 dark:hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-colors duration-200">
                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Return to Admin Dashboard
            </a>
        </div>
    </div>
</div>
<?php endif; ?> 