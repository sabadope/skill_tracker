<?php
// Supervisor Dashboard
require_once "../config/database.php";
require_once "../includes/auth.php";
require_once "../includes/functions.php";

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

// Check if user is logged in and is a supervisor (or admin in view mode)
require_role_with_admin_view('supervisor');

// Check if admin is in view mode
$is_admin_view_mode = is_admin_in_view_mode();
$admin_view_mode = get_admin_view_mode();
$selected_user_id = get_admin_selected_user_id();

// Get database connection
$database = new Database();
$conn = $database->getConnection();

// Get user data
$user_id = $_SESSION['user_id'];
$user = get_user_by_id($conn, $user_id);

// Get all interns
$interns = get_users_by_role($conn, 'intern');

// Get tasks assigned by this supervisor
$assigned_tasks = get_supervisor_tasks($conn, $user_id);

// Get skills requiring attention
$skills_requiring_attention = get_all_skills_requiring_attention($conn);

// Debug: Log the tasks
error_log("=== SUPERVISOR DASHBOARD DEBUG ===");
error_log("Supervisor ID: $user_id");
error_log("Assigned tasks count: " . count($assigned_tasks));
if (!empty($assigned_tasks)) {
    error_log("First task data: " . print_r($assigned_tasks[0], true));
}
error_log("=== SUPERVISOR DASHBOARD DEBUG END ===");

// Calculate intern stats
$total_interns = count($interns);
$interns_by_department = [];
$skills_to_improve = [];

// Calculate advanced/expert interns by department and overall
$advanced_expert_total = 0;
$advanced_expert_by_department = [];
foreach ($interns as $intern) {
    $dept = $intern['department'];
    if (!isset($interns_by_department[$dept])) {
        $interns_by_department[$dept] = 0;
    }
    if (!isset($advanced_expert_by_department[$dept])) {
        $advanced_expert_by_department[$dept] = 0;
    }
    $interns_by_department[$dept]++;
    
    // Get skills for each intern
    $intern_skills = get_user_skills($conn, $intern['id']);
    
    foreach ($intern_skills as $skill) {
        if ($skill['current_level'] === 'Beginner') {
            if (!isset($skills_to_improve[$skill['skill_name']])) {
                $skills_to_improve[$skill['skill_name']] = [
                    'name' => $skill['skill_name'],
                    'category' => $skill['skill_category'],
                    'count' => 0
                ];
            }
            $skills_to_improve[$skill['skill_name']]['count']++;
        }
    }

    $has_advanced = false;
    foreach ($intern_skills as $skill) {
        $level = $skill['supervisor_rating'] ?? $skill['current_level'];
        if (in_array($level, ['Advanced', 'Expert'])) {
            $has_advanced = true;
            break;
        }
    }
    if ($has_advanced) {
        $advanced_expert_total++;
        $advanced_expert_by_department[$dept]++;
    }
}

// Sort skills to improve by count
uasort($skills_to_improve, function($a, $b) {
    return $b['count'] - $a['count'];
});

// Take top 5 skills
$top_skills_to_improve = array_slice($skills_to_improve, 0, 5);

// Include header
require_once "../includes/header.php";
?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 transition-colors duration-300">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php include_once "../includes/admin_view_banner.php"; ?>

        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white mb-10 text-center">
            <?php if ($is_admin_view_mode): ?>
                Supervisor Dashboard (Read-Only View)
            <?php else: ?>
                Supervisor Dashboard
            <?php endif; ?>
        </h1>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-10">
            <!-- Welcome Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 flex flex-col justify-between border border-gray-200 dark:border-gray-700 transition-colors duration-300">
                <div>
                    <h2 class="text-xl font-semibold mb-4 text-gray-700 dark:text-gray-300">Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!</h2>
                    <p class="text-gray-600 dark:text-gray-400 mb-2"><strong>Department:</strong> <?php echo htmlspecialchars($user['department'] ?? 'N/A'); ?></p>
                    <p class="text-gray-600 dark:text-gray-400"><strong>Role:</strong> Supervisor</p>
                </div>
                <div class="mt-6">
                    <?php if (!$is_admin_view_mode): ?>
                    <a href="../analytics/supervisor_view.php" class="inline-block bg-purple-600 hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600 text-white font-bold py-3 px-6 rounded-lg shadow transition duration-200 w-full text-center">
                        View Interns
                    </a>
                    <?php else: ?>
                    <div class="inline-block bg-gray-400 dark:bg-gray-600 text-white font-bold py-3 px-6 rounded-lg shadow w-full text-center cursor-not-allowed opacity-50">
                        View Interns (Read-Only)
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Intern Overview Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 border border-gray-200 dark:border-gray-700 transition-colors duration-300">
                <h2 class="text-xl font-semibold mb-6 text-gray-700 dark:text-gray-300">Intern Overview</h2>
                <div class="grid grid-cols-2 gap-6 mb-6">
                    <div class="text-center">
                        <div class="text-4xl font-bold text-purple-500 dark:text-purple-400"><?php echo $total_interns; ?></div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Total Interns</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl font-bold text-green-500 dark:text-green-400"><?php echo $advanced_expert_total; ?></div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Advanced/Expert Interns</div>
                    </div>
                </div>
                <div class="mt-2">
                    <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Interns by Department</h3>
                    <div class="space-y-2">
                        <?php foreach ($interns_by_department as $dept => $count): ?>
                        <div class="relative pt-1">
                            <div class="flex mb-1 items-center justify-between">
                                <span class="text-xs font-semibold inline-block text-green-700 dark:text-green-300"><?php echo htmlspecialchars($dept) . ': ' . $count; ?></span>
                                <span class="text-xs font-semibold text-green-700 dark:text-green-300">Adv/Expert: <?php echo isset($advanced_expert_by_department[$dept]) ? $advanced_expert_by_department[$dept] : 0; ?></span>
                            </div>
                            <div class="overflow-hidden h-3 mb-2 text-xs flex rounded-full bg-purple-100 dark:bg-purple-900">
                                <div style="width:<?php echo ($total_interns > 0 ? ($count / $total_interns) * 100 : 0); ?>%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-purple-500 dark:bg-purple-400 transition-all duration-500"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <!-- Skills Needing Improvement Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 border border-gray-200 dark:border-gray-700 transition-colors duration-300">
                <h2 class="text-xl font-semibold mb-6 text-gray-700 dark:text-gray-300">Skills Needing Improvement</h2>
                <?php if (empty($top_skills_to_improve)): ?>
                    <p class="text-gray-600 dark:text-gray-400">No skills currently need improvement.</p>
                <?php else: ?>
                    <ul class="space-y-4">
                        <?php foreach ($top_skills_to_improve as $skill): ?>
                        <li class="flex items-center justify-between">
                            <div>
                                <span class="font-medium text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($skill['name']); ?></span>
                                <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">(<?php echo ucfirst($skill['category']); ?>)</span>
                            </div>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">
                                <?php echo $skill['count']; ?> interns
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <div class="mt-6">
                    <?php if (!$is_admin_view_mode): ?>
                    <a href="../skills/evaluate_skill.php" class="text-sm text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-300 font-medium">
                        View all skills →
                    </a>
                    <?php else: ?>
                    <span class="text-sm text-gray-400 dark:text-gray-500 font-medium cursor-not-allowed">
                        View all skills → (Read-Only)
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Interns List -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 border border-gray-200 dark:border-gray-700 transition-colors duration-300 mb-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-300 transition-colors duration-300">
                Intern Directory
            </h2>
                <?php if (!$is_admin_view_mode): ?>
                <a href="../assign_task.php" class="bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white font-medium py-2 px-4 rounded-lg transition duration-200">
                    Assign Task
                </a>
                <?php else: ?>
                <div class="bg-gray-400 dark:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg cursor-not-allowed opacity-50">
                    Assign Task (Read-Only)
                </div>
                <?php endif; ?>
            </div>
            <?php if (empty($interns)): ?>
                <p class="text-gray-600 dark:text-gray-400 transition-colors duration-300">No interns found.</p>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($interns as $intern): ?>
                        <div class="border border-gray-200 dark:border-gray-600 rounded-2xl p-6 shadow hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-200 bg-white dark:bg-gray-800">
                            <div class="font-semibold text-gray-800 dark:text-white transition-colors duration-300"><?php echo htmlspecialchars($intern['first_name'] . ' ' . $intern['last_name']); ?></div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 transition-colors duration-300"><?php echo htmlspecialchars($intern['department']); ?> Department</div>
                            <div class="text-sm text-gray-500 dark:text-gray-500 mt-1 transition-colors duration-300"><?php echo htmlspecialchars($intern['email']); ?></div>
                            <div class="mt-3">
                                <?php if (!$is_admin_view_mode): ?>
                                <a href="../skills/evaluate_skill.php?intern_id=<?php echo $intern['id']; ?>" class="text-sm text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-300 font-medium transition-colors duration-300">
                                    Evaluate Skills →
                                </a>
                                <?php else: ?>
                                <span class="text-sm text-gray-400 dark:text-gray-500 font-medium cursor-not-allowed transition-colors duration-300">
                                    Evaluate Skills → (Read-Only)
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
</div>

        <!-- Skills Requiring Attention -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 border border-gray-200 dark:border-gray-700 transition-colors duration-300 mb-8">
            <h2 class="text-xl font-semibold mb-6 text-gray-700 dark:text-gray-300 flex items-center transition-colors duration-300">
                <i class="fas fa-exclamation-triangle text-yellow-500 mr-3"></i>
                Skills Requiring Attention
        </h2>
            <?php if (empty($skills_requiring_attention)): ?>
                <p class="text-gray-600 dark:text-gray-400">No skills currently require special attention. Great job!</p>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($skills_requiring_attention as $skill): ?>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4 border border-gray-200 dark:border-gray-600">
                            <h3 class="font-semibold text-gray-800 dark:text-white"><?php echo htmlspecialchars($skill['name']); ?></h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3"><?php echo ucfirst($skill['category']); ?> Skill</p>
                            <ul class="space-y-2">
                                <?php foreach ($skill['interns'] as $intern): ?>
                                    <li class="flex justify-between items-center">
                                        <?php if (!$is_admin_view_mode): ?>
                                        <a href="../skills/evaluate_skill.php?intern_id=<?php echo $intern['id']; ?>" class="font-medium text-purple-600 dark:text-purple-400 hover:underline">
                                            <?php echo htmlspecialchars($intern['name']); ?>
                                        </a>
                                        <?php else: ?>
                                        <span class="font-medium text-gray-400 dark:text-gray-500 cursor-not-allowed">
                                            <?php echo htmlspecialchars($intern['name']); ?>
                                        </span>
                                        <?php endif; ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                            <?php if ($intern['level'] === 'Beginner'): ?>
                                                bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200
    <?php else: ?>
                                                bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200
                                            <?php endif; ?>
                                        ">
                                            <?php echo htmlspecialchars($intern['level']); ?>
                            </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            </div>
                        </div>
</div>

<?php
require_once "../includes/footer.php";
?>
