<?php
// Intern Dashboard
require_once "../config/database.php";
require_once "../includes/auth.php";
require_once "../includes/functions.php";

// Handle direct admin view access
if (isset($_GET['admin_view']) && $_GET['admin_view'] == '1' && isset($_GET['user_id'])) {
    // Admin is directly viewing an intern's dashboard
    $viewed_user_id = (int)$_GET['user_id'];
    
    // Verify the user is actually an intern
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->prepare("SELECT id, first_name, last_name, email, department, role FROM users WHERE id = ? AND role = 'intern'");
    $stmt->execute([$viewed_user_id]);
    $viewed_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($viewed_user) {
        // Set up admin view session
        $_SESSION['admin_current_view'] = 'intern';
        $_SESSION['admin_selected_user_id'] = $viewed_user_id;
        
        // Redirect to clean URL
        header("Location: intern_dashboard.php");
        exit();
    }
}

// Check if user is logged in and is an intern (or admin in view mode)
require_role_with_admin_view('intern');

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

// Get user skills
$user_skills = get_user_skills($conn, $user_id);

// Get mentoring tasks assigned to this intern
$tasks = get_intern_tasks($conn, $user_id);

// Calculate skill stats
$total_skills = count($user_skills);
$beginning_skills = 0;
$intermediate_skills = 0;
$advanced_skills = 0;
$expert_skills = 0;

// Use supervisor_rating if available, otherwise fallback to current_level
foreach ($user_skills as $skill) {
    $final_level = isset($skill['supervisor_rating']) && $skill['supervisor_rating'] !== ''
        ? $skill['supervisor_rating']
        : $skill['current_level'];
    switch ($final_level) {
        case 'Beginner':
            $beginning_skills++;
            break;
        case 'Intermediate':
            $intermediate_skills++;
            break;
        case 'Advanced':
            $advanced_skills++;
            break;
        case 'Expert':
            $expert_skills++;
            break;
    }
}

// Include header
require_once "../includes/header.php";
?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 transition-colors duration-300">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php include_once "../includes/admin_view_banner.php"; ?>

        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white mb-10 text-center">
            <?php if ($is_admin_view_mode): ?>
                Intern Dashboard (Read-Only View)
            <?php else: ?>
                Intern Dashboard
            <?php endif; ?>
        </h1>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-10">
            <!-- Welcome Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 flex flex-col justify-between border-t-4 border-blue-500 dark:border-blue-400 transition-colors duration-300">
                <div>
                    <h2 class="text-2xl font-bold mb-4 text-gray-800 dark:text-white flex items-center">
                        <i class="fas fa-hand-sparkles text-blue-500 dark:text-blue-400 mr-3"></i> Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!
                    </h2>
                    <p class="text-gray-700 dark:text-gray-300 text-lg mb-2">Manage your skills and track your progress efficiently.</p>
                    <p class="text-gray-600 dark:text-gray-400 mb-1"><strong>Department:</strong> <span class="font-medium"><?php echo htmlspecialchars($user['department'] ?? 'N/A'); ?></span></p>
                    <p class="text-gray-600 dark:text-gray-400"><strong>Role:</strong> <span class="font-medium">Intern</span></p>
                </div>
                <div class="mt-8">
                    <a href="../skills/rename-skills_list.php" class="inline-block bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 dark:from-blue-600 dark:to-blue-700 dark:hover:from-blue-700 dark:hover:to-blue-800 text-white font-bold py-3 px-6 rounded-lg shadow-md transition duration-300 transform hover:scale-105 w-full text-center">
                        <i class="fas fa-cogs mr-2"></i> Manage My Skills
                    </a>
                </div>
            </div>
            <!-- Skills Overview Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 border-t-4 border-green-500 dark:border-green-400 transition-colors duration-300">
                <h2 class="text-2xl font-bold mb-6 text-gray-800 dark:text-white flex items-center">
                    <i class="fas fa-chart-line text-green-500 dark:text-green-400 mr-3"></i> Skills Overview
                </h2>
                <div class="grid grid-cols-2 gap-6 mb-6 text-center">
                    <div>
                        <div class="text-5xl font-extrabold text-blue-600 dark:text-blue-400 mb-1 leading-none"><?php echo $total_skills; ?></div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Total Skills Tracked</div>
                    </div>
                    <div>
                        <div class="text-5xl font-extrabold text-green-600 dark:text-green-400 mb-1 leading-none"><?php echo $advanced_skills + $expert_skills; ?></div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Advanced/Expert Skills</div>
                    </div>
                </div>
                <div class="mt-4">
                    <!-- Four progress bars for each skill level in 2x2 grid -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="flex mb-1 items-center justify-between">
                                <span class="text-xs font-semibold inline-block py-1 px-3 uppercase rounded-full text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900">Beginner</span>
                                <span class="text-sm font-semibold inline-block text-blue-600 dark:text-blue-400">
                                    <?php echo $total_skills > 0 ? $beginning_skills : 0; ?>/<?php echo $total_skills; ?> (<?php echo $total_skills > 0 ? round(($beginning_skills / $total_skills) * 100) : 0; ?>%)
                                </span>
                            </div>
                            <div class="overflow-hidden h-4 mb-2 text-xs flex rounded-full bg-blue-200 dark:bg-blue-800">
                                <div style="width:<?php echo $total_skills > 0 ? ($beginning_skills / $total_skills) * 100 : 0; ?>%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-blue-600 dark:bg-blue-500 transition-all duration-500 rounded-full"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex mb-1 items-center justify-between">
                                <span class="text-xs font-semibold inline-block py-1 px-3 uppercase rounded-full text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900">Interme..</span>
                                <span class="text-sm font-semibold inline-block text-green-600 dark:text-green-400">
                                    <?php echo $total_skills > 0 ? $intermediate_skills : 0; ?>/<?php echo $total_skills; ?> (<?php echo $total_skills > 0 ? round(($intermediate_skills / $total_skills) * 100) : 0; ?>%)
                                </span>
                            </div>
                            <div class="overflow-hidden h-4 mb-2 text-xs flex rounded-full bg-green-200 dark:bg-green-800">
                                <div style="width:<?php echo $total_skills > 0 ? ($intermediate_skills / $total_skills) * 100 : 0; ?>%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-green-600 dark:bg-green-500 transition-all duration-500 rounded-full"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex mb-1 items-center justify-between">
                                <span class="text-xs font-semibold inline-block py-1 px-3 uppercase rounded-full text-purple-600 dark:text-purple-400 bg-purple-100 dark:bg-purple-900">Advanced</span>
                                <span class="text-sm font-semibold inline-block text-purple-600 dark:text-purple-400">
                                    <?php echo $total_skills > 0 ? $advanced_skills : 0; ?>/<?php echo $total_skills; ?> (<?php echo $total_skills > 0 ? round(($advanced_skills / $total_skills) * 100) : 0; ?>%)
                                </span>
                            </div>
                            <div class="overflow-hidden h-4 mb-2 text-xs flex rounded-full bg-purple-200 dark:bg-purple-800">
                                <div style="width:<?php echo $total_skills > 0 ? ($advanced_skills / $total_skills) * 100 : 0; ?>%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-purple-600 dark:bg-purple-500 transition-all duration-500 rounded-full"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex mb-1 items-center justify-between">
                                <span class="text-xs font-semibold inline-block py-1 px-3 uppercase rounded-full text-yellow-600 dark:text-yellow-400 bg-yellow-100 dark:bg-yellow-900">Expert</span>
                                <span class="text-sm font-semibold inline-block text-yellow-600 dark:text-yellow-400">
                                    <?php echo $total_skills > 0 ? $expert_skills : 0; ?>/<?php echo $total_skills; ?> (<?php echo $total_skills > 0 ? round(($expert_skills / $total_skills) * 100) : 0; ?>%)
                                </span>
                            </div>
                            <div class="overflow-hidden h-4 mb-2 text-xs flex rounded-full bg-yellow-200 dark:bg-yellow-800">
                                <div style="width:<?php echo $total_skills > 0 ? ($expert_skills / $total_skills) * 100 : 0; ?>%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-yellow-500 dark:bg-yellow-400 transition-all duration-500 rounded-full"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Skill Distribution Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 border-t-4 border-purple-500 dark:border-purple-400 transition-colors duration-300">
                <h2 class="text-2xl font-bold mb-6 text-gray-800 dark:text-white flex items-center">
                    <i class="fas fa-chart-pie text-purple-500 dark:text-purple-400 mr-3"></i> Skill Distribution
                </h2>
                <div class="h-56 flex items-center justify-center">
                    <canvas id="skillDistributionChart" style="max-height: 220px;" width="400" height="220"></canvas>
                </div>
            </div>
        </div>

        <!-- Assigned Tasks Section -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 mb-10 border-t-4 border-indigo-500 dark:border-indigo-400 transition-colors duration-300">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center">
                <i class="fas fa-tasks text-indigo-500 dark:text-indigo-400 mr-3"></i> Assigned Tasks & Mentoring Sessions
            </h2>
                <?php if (!$is_admin_view_mode): ?>
                <a href="../my_tasks.php" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200 ml-4">
                    Manage Tasks
                </a>
                <?php else: ?>
                <div class="bg-gray-400 dark:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg cursor-not-allowed opacity-50 ml-4">
                    Manage Tasks (Read-Only)
                </div>
                <?php endif; ?>
            </div>
            <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                <p class="text-sm text-blue-700 dark:text-blue-300">
                    <i class="fas fa-info-circle mr-2"></i>
                    Task statuses are managed by your supervisor. Contact them if you need to discuss task progress or completion.
                </p>
            </div>
            <?php if (empty($tasks)): ?>
                <div class="text-center py-10 text-gray-500 dark:text-gray-400">
                    <i class="fas fa-clipboard-check text-6xl mb-4"></i>
                    <p class="text-lg">No tasks assigned yet. Check back later!</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white dark:bg-gray-800 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="py-3 px-5 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600">Task Title & Description</th>
                                <th class="py-3 px-5 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600">Associated Skill</th>
                                <th class="py-3 px-5 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600">Assigned By</th>
                                <th class="py-3 px-5 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600">Due Date</th>
                                <th class="py-3 px-5 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($tasks as $task): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                                    <td class="py-3 px-5">
                                        <div class="font-medium text-gray-900 dark:text-white text-base mb-1"><?php echo htmlspecialchars($task['title']); ?></div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($task['description']); ?></div>
                                    </td>
                                    <td class="py-3 px-5 text-gray-700 dark:text-gray-300 text-sm"><?php echo htmlspecialchars($task['skill_name'] ?? 'General'); ?></td>
                                    <td class="py-3 px-5 text-gray-700 dark:text-gray-300 text-sm"><?php echo htmlspecialchars($task['supervisor_first_name'] . ' ' . $task['supervisor_last_name']); ?></td>
                                    <td class="py-3 px-5 text-gray-700 dark:text-gray-300 text-sm"><?php echo date('M d, Y', strtotime($task['due_date'])); ?></td>
                                    <td class="py-3 px-5">
                                        <?php 
                                            $status_colors = [
                                                'pending' => 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200',
                                                'in_progress' => 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200',
                                                'pending_confirmation' => 'bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200',
                                                'completed' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
                                                'cancelled' => 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200'
                                            ];
                                            $status_color = $status_colors[$task['status']] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200';
                                        ?>
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_color; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Skills Requiring Attention Section -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 border-t-4 border-red-500 dark:border-red-400 transition-colors duration-300">
            <h2 class="text-2xl font-bold mb-6 text-gray-800 dark:text-white flex items-center">
                <i class="fas fa-exclamation-triangle text-red-500 dark:text-red-400 mr-3"></i> Skills Requiring Attention
            </h2>
            <?php
            // Filter skills that need improvement based on supervisor's final verdict
            $improvement_needed = array_filter($user_skills, function($skill) {
                // Only show skills that supervisor has rated as Beginner or Intermediate
                if (isset($skill['supervisor_rating']) && $skill['supervisor_rating'] !== '') {
                    return in_array($skill['supervisor_rating'], ['Beginner', 'Intermediate']);
                    }
                // If no supervisor rating yet, don't show in improvement needed
                return false;
            });
            ?>
            <?php if (empty($improvement_needed)): ?>
                <div class="text-center py-10 text-gray-500 dark:text-gray-400">
                    <i class="fas fa-check-circle text-6xl text-green-500 dark:text-green-400 mb-4"></i>
                    <p class="text-lg">No skills currently need improvement. Keep up the great work!</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($improvement_needed as $skill): ?>
                        <?php
                        // Determine color scheme based on supervisor rating
                        $is_beginner = $skill['supervisor_rating'] === 'Beginner';
                        $bg_color = $is_beginner ? 'bg-blue-50 dark:bg-blue-900/20' : 'bg-green-50 dark:bg-green-900/20';
                        $border_color = $is_beginner ? 'border-blue-200 dark:border-blue-800' : 'border-green-200 dark:border-green-800';
                        $text_color = $is_beginner ? 'text-blue-800 dark:text-blue-200' : 'text-green-800 dark:text-green-200';
                        $badge_bg = $is_beginner ? 'bg-blue-100 dark:bg-blue-800' : 'bg-green-100 dark:bg-green-800';
                        $badge_text = $is_beginner ? 'text-blue-800 dark:text-blue-200' : 'text-green-800 dark:text-green-200';
                        $detail_color = $is_beginner ? 'text-blue-700 dark:text-blue-300' : 'text-green-700 dark:text-green-300';
                        $button_bg = $is_beginner ? 'bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600' : 'bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600';
                        ?>
                        <div class="<?php echo $bg_color; ?> border <?php echo $border_color; ?> rounded-lg p-6 transition-colors duration-300">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold <?php echo $text_color; ?>"><?php echo htmlspecialchars($skill['skill_name']); ?></h3>
                                <span class="px-2 py-1 text-xs font-medium <?php echo $badge_bg; ?> <?php echo $badge_text; ?> rounded-full">
                                    <?php echo htmlspecialchars($skill['supervisor_rating']); ?>
                                </span>
                            </div>
                            <div class="space-y-2 text-sm <?php echo $detail_color; ?>">
                                <p><strong>Your Self-Assessment:</strong> <?php echo htmlspecialchars($skill['current_level']); ?></p>
                                <p><strong>Supervisor's Rating:</strong> <?php echo htmlspecialchars($skill['supervisor_rating']); ?></p>
                                <p><strong>Last Updated:</strong> <?php echo isset($skill['last_updated']) ? date('M d, Y', strtotime($skill['last_updated'])) : 'N/A'; ?></p>
                            </div>
                            <div class="mt-4">
                                <?php if (!$is_admin_view_mode): ?>
                                <a href="../skills/rename-skills_list.php" class="inline-block <?php echo $button_bg; ?> text-white text-sm font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                                    Update Skill
                                </a>
                                <?php else: ?>
                                <div class="inline-block bg-gray-400 dark:bg-gray-600 text-white text-sm font-medium py-2 px-4 rounded-lg cursor-not-allowed opacity-50">
                                    Update Skill (Read-Only)
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Skill Distribution Chart
    var ctx = document.getElementById('skillDistributionChart').getContext('2d');
    var skillDistributionChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Beginner', 'Intermediate', 'Advanced', 'Expert'],
            datasets: [{
                data: [
                    <?php echo $beginning_skills; ?>,
                    <?php echo $intermediate_skills; ?>,
                    <?php echo $advanced_skills; ?>,
                    <?php echo $expert_skills; ?>
                ],
                backgroundColor: [
                    '#3182CE', // blue-500
                    '#48BB78', // green-500
                    '#9F7AEA', // purple-500
                    '#E53E3E'  // red-500
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                position: 'right',
                labels: {
                    fontColor: '#718096'
                }
            },
            tooltips: {
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
            },
            plugins: {
                tooltip: {
                    enabled: true,
                    mode: 'index',
                    intersect: false,
                },
                legend: {
                    display: true,
                    position: 'right',
                    labels: {
                        color: '#4A5568', // text-gray-700
                        font: {
                            size: 14,
                            family: 'sans-serif',
                        }
                    }
                }
            },
            // Increased chart area for better visibility
            layout: {
                padding: {
                    left: 0,
                    right: 0,
                    top: 10,
                    bottom: 0
                }
            }
        }
    });
});
</script>

<?php
require_once "../includes/footer.php";
?>
