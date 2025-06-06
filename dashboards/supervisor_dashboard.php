<?php
// Supervisor Dashboard
require_once "../config/database.php";
require_once "../includes/auth.php";
require_once "../includes/functions.php";

// Check if user is logged in and is a supervisor
require_role('supervisor');

// Get database connection
$database = new Database();
$conn = $database->getConnection();

// Get user data
$user_id = $_SESSION['user_id'];
$user = get_user_by_id($conn, $user_id);

// Get all interns
$interns = get_users_by_role($conn, 'intern');

// Get mentoring tasks assigned by this supervisor
$tasks = get_supervisor_tasks($conn, $user_id);

// Calculate intern stats
$total_interns = count($interns);
$interns_by_department = [];
$skills_to_improve = [];

foreach ($interns as $intern) {
    $dept = $intern['department'];
    if (!isset($interns_by_department[$dept])) {
        $interns_by_department[$dept] = 0;
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

<h1 class="text-3xl font-bold mb-6">Supervisor Dashboard</h1>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4 text-gray-700">Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!</h2>
        <p class="text-gray-600 mb-2"><strong>Department:</strong> <?php echo htmlspecialchars($user['department']); ?></p>
        <p class="text-gray-600"><strong>Role:</strong> Supervisor</p>
        <div class="mt-4">
            <a href="../skills/evaluate_skill.php" class="inline-block bg-purple-500 hover:bg-purple-600 text-white font-bold py-2 px-4 rounded transition duration-300">
                Evaluate Interns
            </a>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4 text-gray-700">Intern Overview</h2>
        <div class="grid grid-cols-2 gap-4">
            <div class="text-center">
                <div class="text-4xl font-bold text-purple-500"><?php echo $total_interns; ?></div>
                <div class="text-sm text-gray-500">Total Interns</div>
            </div>
            <div class="text-center">
                <div class="text-4xl font-bold text-green-500"><?php echo count($tasks); ?></div>
                <div class="text-sm text-gray-500">Assigned Tasks</div>
            </div>
        </div>
        <div class="mt-4">
            <h3 class="font-semibold text-gray-700 mb-2">Interns by Department</h3>
            <div class="space-y-2">
                <?php foreach ($interns_by_department as $dept => $count): ?>
                <div class="relative pt-1">
                    <div class="flex mb-1 items-center justify-between">
                        <div>
                            <span class="text-xs font-semibold inline-block text-gray-700">
                                <?php echo htmlspecialchars($dept); ?>
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-semibold inline-block text-gray-700">
                                <?php echo $count; ?>
                            </span>
                        </div>
                    </div>
                    <div class="overflow-hidden h-2 mb-2 text-xs flex rounded bg-purple-200">
                        <div style="width:<?php echo ($count / $total_interns) * 100; ?>%" 
                             class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-purple-500"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4 text-gray-700">Skills Needing Improvement</h2>
        <?php if (empty($top_skills_to_improve)): ?>
            <p class="text-gray-600">No skills currently need improvement.</p>
        <?php else: ?>
            <ul class="space-y-3">
                <?php foreach ($top_skills_to_improve as $skill): ?>
                <li class="flex items-center justify-between">
                    <div>
                        <span class="font-medium text-gray-800"><?php echo htmlspecialchars($skill['name']); ?></span>
                        <span class="text-xs text-gray-500 ml-1">(<?php echo ucfirst($skill['category']); ?>)</span>
                    </div>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                        <?php echo $skill['count']; ?> interns
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <div class="mt-4">
            <a href="../skills/evaluate_skill.php" class="text-sm text-purple-600 hover:text-purple-800 font-medium">
                View all skills →
            </a>
        </div>
    </div>
</div>

<!-- Current Assignments Section -->
<div class="bg-white rounded-lg shadow-md p-6 mb-8">
    <h2 class="text-xl font-semibold mb-4 text-gray-700">Current Assignments</h2>
    
    <?php if (empty($tasks)): ?>
        <p class="text-gray-600">No tasks assigned yet.</p>
        <div class="mt-4">
            <a href="../skills/evaluate_skill.php" class="inline-block bg-purple-500 hover:bg-purple-600 text-white font-bold py-2 px-4 rounded transition duration-300">
                Assign New Task
            </a>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead>
                    <tr>
                        <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Intern
                        </th>
                        <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Task
                        </th>
                        <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Skill
                        </th>
                        <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Due Date
                        </th>
                        <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Status
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td class="py-3 px-4 border-b border-gray-200 text-gray-500">
                                <?php echo htmlspecialchars($task['intern_first_name'] . ' ' . $task['intern_last_name']); ?>
                            </td>
                            <td class="py-3 px-4 border-b border-gray-200">
                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars($task['title']); ?></div>
                                <div class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($task['description']); ?></div>
                            </td>
                            <td class="py-3 px-4 border-b border-gray-200 text-gray-500">
                                <?php echo htmlspecialchars($task['skill_name'] ?? 'General'); ?>
                            </td>
                            <td class="py-3 px-4 border-b border-gray-200 text-gray-500">
                                <?php echo date('M d, Y', strtotime($task['due_date'])); ?>
                            </td>
                            <td class="py-3 px-4 border-b border-gray-200">
                                <?php 
                                    $status_colors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'in_progress' => 'bg-blue-100 text-blue-800',
                                        'completed' => 'bg-green-100 text-green-800',
                                        'cancelled' => 'bg-red-100 text-red-800'
                                    ];
                                    $status_color = $status_colors[$task['status']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_color; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            <a href="../skills/evaluate_skill.php" class="inline-block bg-purple-500 hover:bg-purple-600 text-white font-bold py-2 px-4 rounded transition duration-300">
                Assign New Task
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Interns List -->
<div class="bg-white rounded-lg shadow-md p-6">
    <h2 class="text-xl font-semibold mb-4 text-gray-700">Intern Directory</h2>
    
    <?php if (empty($interns)): ?>
        <p class="text-gray-600">No interns found.</p>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($interns as $intern): ?>
                <div class="border rounded-lg p-4 hover:bg-gray-50 transition duration-200">
                    <div class="font-semibold text-gray-800"><?php echo htmlspecialchars($intern['first_name'] . ' ' . $intern['last_name']); ?></div>
                    <div class="text-sm text-gray-600"><?php echo htmlspecialchars($intern['department']); ?> Department</div>
                    <div class="text-sm text-gray-500 mt-1"><?php echo htmlspecialchars($intern['email']); ?></div>
                    <div class="mt-3">
                        <a href="../skills/evaluate_skill.php?intern_id=<?php echo $intern['id']; ?>" 
                           class="text-sm text-purple-600 hover:text-purple-800 font-medium">
                            Evaluate Skills →
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
require_once "../includes/footer.php";
?>
