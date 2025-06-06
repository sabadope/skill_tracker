<?php
// Admin Dashboard
require_once "../config/constants.php";
require_once "../config/database.php";
require_once "../includes/auth.php";
require_once "../includes/functions.php";

// Check if user is logged in and is an admin
require_role('admin');

// Get database connection
$database = new Database();
$conn = $database->getConnection();

// Get all users
$interns = get_users_by_role($conn, 'intern');
$supervisors = get_users_by_role($conn, 'supervisor');
$admins = get_users_by_role($conn, 'admin');

// Get top performing interns
$top_interns = get_top_interns($conn, 5);

// Get skill gap analysis
$skill_gaps = identify_skill_gaps($conn);

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

// Include header
require_once "../includes/header.php";
?>

<h1 class="text-3xl font-bold mb-6">Admin Dashboard</h1>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4 text-gray-700">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
        <p class="text-gray-600 mb-6"><strong>Role:</strong> Admin</p>
        <div class="grid grid-cols-2 gap-2">
            <a href="../register.php" class="inline-block bg-red-500 hover:bg-red-600 text-white text-center font-bold py-2 px-4 rounded transition duration-300">
                Add User
            </a>
            <a href="../reports/generate_report.php" class="inline-block bg-blue-500 hover:bg-blue-600 text-white text-center font-bold py-2 px-4 rounded transition duration-300">
                Reports
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4 text-gray-700">Users Overview</h2>
        <div class="flex justify-between items-center mb-4">
            <span class="text-sm font-medium text-gray-600">Total Users</span>
            <span class="text-2xl font-bold text-gray-800"><?php echo $total_users; ?></span>
        </div>
        <canvas id="userDistributionChart" style="height: 200px; max-height: 250px;" width="400" height="150"></canvas>
    </div>



    <div class="bg-white rounded-lg shadow-md p-6">
    <h2 class="text-xl font-semibold mb-4 text-gray-700">Users by Department</h2>
    <div class="space-y-3">
        <?php foreach (array_slice($departments, 0, 5) as $dept => $count): ?>
            <div class="flex justify-between items-center">
                <span class="text-sm font-medium text-gray-600"><?php echo htmlspecialchars($dept); ?></span>
                <span class="text-sm font-semibold text-gray-800"><?php echo $count; ?></span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-blue-500 h-2 rounded-full" style="width: <?php echo ($count / $total_users) * 100; ?>%"></div>
            </div>
        <?php endforeach; ?>
    </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow-md p-6">
    <h2 class="text-xl font-semibold mb-4 text-gray-700">Quick Actions</h2>
    <div class="space-y-2">
        <a href="../register.php?role=intern" class="block p-3 bg-gray-100 hover:bg-gray-200 rounded transition duration-200">
            <div class="font-medium text-gray-800">Add New Intern</div>
            <div class="text-xs text-gray-500">Create intern account</div>
        </a>
        <a href="../register.php?role=supervisor" class="block p-3 bg-gray-100 hover:bg-gray-200 rounded transition duration-200">
            <div class="font-medium text-gray-800">Add New Supervisor</div>
            <div class="text-xs text-gray-500">Create supervisor account</div>
        </a>
        <a href="../reports/generate_report.php" class="block p-3 bg-gray-100 hover:bg-gray-200 rounded transition duration-200">
            <div class="font-medium text-gray-800">Generate Reports</div>
            <div class="text-xs text-gray-500">View skill analytics</div>
        </a>
        <a href="../reports/export_pdf.php" class="block p-3 bg-gray-100 hover:bg-gray-200 rounded transition duration-200">
            <div class="font-medium text-gray-800">Export to PDF</div>
            <div class="text-xs text-gray-500">Download reports</div>
        </a>
    </div>
    </div>

    <!-- Top Performing Interns -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4 text-gray-700">Top Performing Interns</h2>
        
        <?php if (empty($top_interns)): ?>
            <p class="text-gray-600">No intern performance data available yet.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Intern
                            </th>
                            <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Department
                            </th>
                            <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Advanced Skills
                            </th>
                            <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Proficiency
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_interns as $intern): ?>
                            <tr>
                                <td class="py-3 px-4 border-b border-gray-200 text-gray-800 font-medium">
                                    <?php echo htmlspecialchars($intern['first_name'] . ' ' . $intern['last_name']); ?>
                                </td>
                                <td class="py-3 px-4 border-b border-gray-200 text-gray-600">
                                    <?php echo htmlspecialchars($intern['department']); ?>
                                </td>
                                <td class="py-3 px-4 border-b border-gray-200 text-gray-600">
                                    <?php echo $intern['advanced_skills'] . '/' . $intern['total_skills']; ?>
                                </td>
                                <td class="py-3 px-4 border-b border-gray-200">
                                    <div class="flex items-center">
                                        <div class="w-full bg-gray-200 rounded-full h-2 mr-2">
                                            <div class="bg-green-500 h-2 rounded-full" style="width: <?php echo $intern['proficiency_percentage']; ?>%"></div>
                                        </div>
                                        <span class="text-xs font-medium text-gray-600"><?php echo round($intern['proficiency_percentage']); ?>%</span>
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

<div class="grid grid-cols-1 md:grid-cols-1 gap-6 mb-8">
    
    <!-- Skill Gaps -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4 text-gray-700">Skills with Largest Gaps</h2>
        
        <?php if (empty($top_skill_gaps)): ?>
            <p class="text-gray-600">No skill gap data available yet.</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($top_skill_gaps as $skill): ?>
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <div>
                                <span class="font-medium text-gray-800"><?php echo htmlspecialchars($skill['name']); ?></span>
                                <span class="text-xs text-gray-500 ml-1">(<?php echo ucfirst($skill['category']); ?>)</span>
                            </div>
                            <span class="text-sm font-medium text-gray-600">
                                <?php echo $skill['beginners']; ?> beginners (<?php echo round($skill['beginner_percentage']); ?>%)
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-red-500 h-2 rounded-full" style="width: <?php echo $skill['beginner_percentage']; ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-4 text-right">
                <a href="../reports/generate_report.php" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                    View Full Report →
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- User Management -->
<div class="bg-white rounded-lg shadow-md p-6 mb-8">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-semibold text-gray-700">User Management</h2>
        <a href="../register.php" class="bg-blue-500 hover:bg-blue-600 text-white text-sm font-bold py-2 px-4 rounded transition duration-300">
            Add New User
        </a>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead>
                <tr>
                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Name
                    </th>
                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Username
                    </th>
                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Email
                    </th>
                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Department
                    </th>
                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Role
                    </th>
                </tr>
            </thead>
            <tbody>
                <!-- Display up to 10 users -->
                <?php 
                $users = array_merge($interns, $supervisors, $admins);
                usort($users, function($a, $b) {
                    return strcmp($a['last_name'], $b['last_name']);
                });
                $users = array_slice($users, 0, 10);
                ?>
                
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="py-3 px-4 border-b border-gray-200">
                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                        </td>
                        <td class="py-3 px-4 border-b border-gray-200 text-gray-500">
                            <?php echo htmlspecialchars($user['username']); ?>
                        </td>
                        <td class="py-3 px-4 border-b border-gray-200 text-gray-500">
                            <?php echo htmlspecialchars($user['email']); ?>
                        </td>
                        <td class="py-3 px-4 border-b border-gray-200 text-gray-500">
                            <?php echo htmlspecialchars($user['department']); ?>
                        </td>
                        <td class="py-3 px-4 border-b border-gray-200">
                            <?php 
                                $role_colors = [
                                    'intern' => 'bg-blue-100 text-blue-800',
                                    'supervisor' => 'bg-purple-100 text-purple-800',
                                    'admin' => 'bg-red-100 text-red-800'
                                ];
                                $role_color = $role_colors[$user['role']] ?? 'bg-gray-100 text-gray-800';
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
    <?php if (count($users) < $total_users): ?>
    <div class="mt-4 text-right">
        <span class="text-sm text-gray-600">Showing 10 of <?php echo $total_users; ?> users</span>
    </div>
    <?php endif; ?>
</div>

<script>
// Initialize charts once and stop automatic reloading
var chartInitialized = false;

function initializeCharts() {
    if (chartInitialized) return;
    
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
                borderWidth: 1
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
});
</script>

<?php
require_once "../includes/footer.php";
?>
