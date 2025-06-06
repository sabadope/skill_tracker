<?php
// Intern Dashboard
require_once "../config/database.php";
require_once "../includes/auth.php";
require_once "../includes/functions.php";

// Check if user is logged in and is an intern
require_role('intern');

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

foreach ($user_skills as $skill) {
    switch ($skill['current_level']) {
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

<h1 class="text-3xl font-bold mb-6">Intern Dashboard</h1>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4 text-gray-700">Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!</h2>
        <p class="text-gray-600 mb-2"><strong>Department:</strong> <?php echo htmlspecialchars($user['department']); ?></p>
        <p class="text-gray-600"><strong>Role:</strong> Intern</p>
        <div class="mt-4">
            <a href="../skills/skills_list.php" class="inline-block bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded transition duration-300">
                Update Skills
            </a>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4 text-gray-700">Skills Overview</h2>
        <div class="grid grid-cols-2 gap-4">
            <div class="text-center">
                <div class="text-4xl font-bold text-blue-500"><?php echo $total_skills; ?></div>
                <div class="text-sm text-gray-500">Total Skills</div>
            </div>
            <div class="text-center">
                <div class="text-4xl font-bold text-green-500"><?php echo $advanced_skills + $expert_skills; ?></div>
                <div class="text-sm text-gray-500">Advanced/Expert</div>
            </div>
        </div>
        <div class="mt-4">
            <div class="relative pt-1">
                <div class="flex mb-2 items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-blue-600 bg-blue-200">
                            Skill Progress
                        </span>
                    </div>
                    <div class="text-right">
                        <span class="text-xs font-semibold inline-block text-blue-600">
                            <?php echo $total_skills > 0 ? round((($intermediate_skills + $advanced_skills + $expert_skills) / $total_skills) * 100) : 0; ?>%
                        </span>
                    </div>
                </div>
                <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-blue-200">
                    <div style="width:<?php echo $total_skills > 0 ? (($intermediate_skills + $advanced_skills + $expert_skills) / $total_skills) * 100 : 0; ?>%" 
                         class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-blue-500"></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4 text-gray-700">Skill Distribution</h2>
        <canvas id="skillDistributionChart" style="height: 200px; max-height: 250px;" width="400" height="150"></canvas>
    </div>
</div>

<!-- Pending Tasks Section -->
<div class="bg-white rounded-lg shadow-md p-6 mb-8">
    <h2 class="text-xl font-semibold mb-4 text-gray-700">Assigned Tasks & Mentoring Sessions</h2>
    
    <?php if (empty($tasks)): ?>
        <p class="text-gray-600">No tasks assigned yet.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead>
                    <tr>
                        <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Task
                        </th>
                        <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Skill
                        </th>
                        <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Supervisor
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
                            <td class="py-3 px-4 border-b border-gray-200">
                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars($task['title']); ?></div>
                                <div class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($task['description']); ?></div>
                            </td>
                            <td class="py-3 px-4 border-b border-gray-200 text-gray-500">
                                <?php echo htmlspecialchars($task['skill_name'] ?? 'General'); ?>
                            </td>
                            <td class="py-3 px-4 border-b border-gray-200 text-gray-500">
                                <?php echo htmlspecialchars($task['supervisor_first_name'] . ' ' . $task['supervisor_last_name']); ?>
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
    <?php endif; ?>
</div>

<!-- Recent Skills Activity -->
<div class="bg-white rounded-lg shadow-md p-6">
    <h2 class="text-xl font-semibold mb-4 text-gray-700">Skills Requiring Attention</h2>
    
    <?php
    // Filter skills that need improvement (beginner level or supervisor rating lower than self-assessment)
    $improvement_needed = array_filter($user_skills, function($skill) {
        return $skill['current_level'] === 'Beginner' || 
               ($skill['supervisor_rating'] && 
                (
                    ($skill['supervisor_rating'] === 'Beginner' && $skill['current_level'] !== 'Beginner') ||
                    ($skill['supervisor_rating'] === 'Intermediate' && in_array($skill['current_level'], ['Advanced', 'Expert'])) ||
                    ($skill['supervisor_rating'] === 'Advanced' && $skill['current_level'] === 'Expert')
                )
               );
    });
    ?>
    
    <?php if (empty($improvement_needed)): ?>
        <p class="text-gray-600">No skills currently need improvement. Great work!</p>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($improvement_needed as $skill): ?>
                <div class="border rounded-lg p-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($skill['skill_name']); ?></h3>
                            <p class="text-sm text-gray-500"><?php echo ucfirst($skill['skill_category']); ?> Skill</p>
                        </div>
                        <div>
                            <?php 
                                $color = get_level_color($skill['current_level']);
                                $bg_color = "bg-{$color}-100";
                                $text_color = "text-{$color}-800";
                            ?>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $bg_color . ' ' . $text_color; ?>">
                                <?php echo $skill['current_level']; ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if ($skill['supervisor_rating']): ?>
                        <div class="mt-2 text-sm">
                            <span class="font-semibold">Supervisor Rating:</span> 
                            <?php 
                                $sup_color = get_level_color($skill['supervisor_rating']);
                                $sup_bg_color = "bg-{$sup_color}-100";
                                $sup_text_color = "text-{$sup_color}-800";
                            ?>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $sup_bg_color . ' ' . $sup_text_color; ?>">
                                <?php echo $skill['supervisor_rating']; ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($skill['supervisor_comments']): ?>
                        <div class="mt-2 text-sm">
                            <p class="font-semibold mb-1">Supervisor Feedback:</p>
                            <p class="text-gray-600 italic">"<?php echo htmlspecialchars($skill['supervisor_comments']); ?>"</p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-3 text-sm">
                        <p class="font-semibold">Learning Suggestion:</p>
                        <p class="text-gray-600"><?php echo get_learning_suggestions($skill['current_level'], $skill['skill_name']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
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
            }
        }
    });
});
</script>

<?php
require_once "../includes/footer.php";
?>
