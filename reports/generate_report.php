<?php
// Generate Reports Page for Admin
require_once "../config/database.php";
require_once "../includes/auth.php";
require_once "../includes/functions.php";

// Check if user is logged in and is an admin
require_role('admin');

// Get database connection
$database = new Database();
$conn = $database->getConnection();

// Get filter parameters from form submission
$department = isset($_GET['department']) ? sanitize_input($_GET['department']) : '';
$skill_id = isset($_GET['skill_id']) ? (int)$_GET['skill_id'] : 0;
$time_period = isset($_GET['time_period']) ? (int)$_GET['time_period'] : 0;

// Get all departments for filter dropdown
$departments = [];
$dept_query = $conn->query("SELECT DISTINCT department FROM users WHERE role = 'intern' ORDER BY department");
while ($row = $dept_query->fetch(PDO::FETCH_ASSOC)) {
    $departments[] = $row['department'];
}

// Get all skills for filter dropdown
$all_skills = get_all_skills($conn);

// Get number of skills to show
$num_skills = isset($_GET['num_skills']) ? (int)$_GET['num_skills'] : 5;

// Get skill growth data with filters
$skill_growth_data = get_skill_growth_data($conn, $department, $skill_id, $time_period, $num_skills);

// Get top performing interns
$top_interns = get_top_interns($conn, 10);

// Get skill gaps
$skill_gaps = identify_skill_gaps($conn);

// Include header
require_once "../includes/header.php";
?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 transition-colors duration-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
            <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white mb-4 md:mb-0 transition-colors duration-200">Skills Analytics & Reports</h1>
            <a href="export_pdf.php<?php echo !empty($_GET) ? '?' . http_build_query($_GET) : ''; ?>" 
               class="inline-flex items-center bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-6 rounded-lg shadow transition duration-300">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                Export to PDF
            </a>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-8 mb-10 transition-colors duration-200">
            <h2 class="text-xl font-semibold mb-6 text-gray-700 dark:text-gray-300 flex items-center transition-colors duration-200">
                <svg class="w-6 h-6 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v16a1 1 0 01-1 1H4a1 1 0 01-1-1V4z" /></svg>
                Filter Reports
            </h2>
            <form method="GET" action="generate_report.php">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 transition-colors duration-200" for="department">
                            Department
                        </label>
                        <select name="department" id="department" class="shadow appearance-none border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg w-full py-2 px-3 text-gray-700 dark:text-gray-300 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo ($department === $dept) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 transition-colors duration-200" for="skill_id">
                            Skill
                        </label>
                        <select name="skill_id" id="skill_id" class="shadow appearance-none border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg w-full py-2 px-3 text-gray-700 dark:text-gray-300 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200">
                            <option value="">All Skills</option>
                            <?php 
                            $skill_categories = [];
                            foreach ($all_skills as $skill) {
                                if (!isset($skill_categories[$skill['category']])) {
                                    $skill_categories[$skill['category']] = [];
                                }
                                $skill_categories[$skill['category']][] = $skill;
                            }
                            ?>
                            <?php foreach ($skill_categories as $category => $skills): ?>
                                <optgroup label="<?php echo ucfirst($category); ?> Skills">
                                    <?php foreach ($skills as $skill): ?>
                                        <option value="<?php echo $skill['id']; ?>" <?php echo ($skill_id == $skill['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($skill['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 transition-colors duration-200" for="time_period">
                            Time Period
                        </label>
                        <select name="time_period" id="time_period" class="shadow appearance-none border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg w-full py-2 px-3 text-gray-700 dark:text-gray-300 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200">
                            <option value="">All Time</option>
                            <option value="30" <?php echo ($time_period == 30) ? 'selected' : ''; ?>>Last 30 Days</option>
                            <option value="90" <?php echo ($time_period == 90) ? 'selected' : ''; ?>>Last 90 Days</option>
                            <option value="180" <?php echo ($time_period == 180) ? 'selected' : ''; ?>>Last 6 Months</option>
                            <option value="365" <?php echo ($time_period == 365) ? 'selected' : ''; ?>>Last Year</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex justify-end">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-6 rounded-lg shadow transition duration-300">
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Skill Growth Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-8 mb-10 transition-colors duration-200">
            <div class="mb-4 flex items-center">
                <label for="num_skills" class="mr-2 font-medium text-gray-700 dark:text-gray-300">Show Top Skills:</label>
                <form method="GET" action="generate_report.php" class="inline-block">
                    <input type="hidden" name="department" value="<?php echo htmlspecialchars($department); ?>">
                    <input type="hidden" name="skill_id" value="<?php echo htmlspecialchars($skill_id); ?>">
                    <input type="hidden" name="time_period" value="<?php echo htmlspecialchars($time_period); ?>">
                    <select name="num_skills" id="num_skills" class="border rounded px-2 py-1" onchange="this.form.submit()">
                        <?php foreach ([0,3,5,7,9] as $n): ?>
                            <option value="<?php echo $n; ?>" <?php echo $num_skills == $n ? 'selected' : ''; ?>><?php echo $n == 0 ? 'All' : $n; ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <h2 class="text-xl font-semibold mb-6 text-gray-700 dark:text-gray-300 flex items-center transition-colors duration-200">
                <svg class="w-6 h-6 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                Skill Growth Analysis
            </h2>
            <?php if (empty($skill_growth_data)): ?>
                <p class="text-gray-600 dark:text-gray-400 transition-colors duration-200">No skill growth data available for the selected filters.</p>
            <?php else: ?>
                <div class="mb-8">
                    <canvas id="skillGrowthChart" width="800" height="400"></canvas>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg transition-colors duration-200">
                        <thead>
                            <tr>
                                <th class="py-2 px-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider transition-colors duration-200">Skill</th>
                                <th class="py-2 px-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider transition-colors duration-200">Category</th>
                                <th class="py-2 px-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider transition-colors duration-200" colspan="2">Initial Level</th>
                                <th class="py-2 px-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider transition-colors duration-200" colspan="2">Current Level</th>
                                <th class="py-2 px-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider transition-colors duration-200">Growth</th>
                            </tr>
                            <tr>
                                <th class="py-2 px-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700"></th>
                                <th class="py-2 px-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700"></th>
                                <th class="py-2 px-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 transition-colors duration-200">Beg/Int</th>
                                <th class="py-2 px-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 transition-colors duration-200">Adv/Exp</th>
                                <th class="py-2 px-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 transition-colors duration-200">Beg/Int</th>
                                <th class="py-2 px-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 transition-colors duration-200">Adv/Exp</th>
                                <th class="py-2 px-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($skill_growth_data as $skill): ?>
                                <?php
                                    $initial_total = $skill['initial_beginner'] + $skill['initial_intermediate'] + $skill['initial_advanced'] + $skill['initial_expert'];
                                    $current_total = $skill['current_beginner'] + $skill['current_intermediate'] + $skill['current_advanced'] + $skill['current_expert'];
                                    $initial_basic = $skill['initial_beginner'] + $skill['initial_intermediate'];
                                    $initial_advanced = $skill['initial_advanced'] + $skill['initial_expert'];
                                    $current_basic = $skill['current_beginner'] + $skill['current_intermediate'];
                                    $current_advanced = $skill['current_advanced'] + $skill['current_expert'];
                                    $initial_advanced_percent = $initial_total > 0 ? round(($initial_advanced / $initial_total) * 100) : 0;
                                    $current_advanced_percent = $current_total > 0 ? round(($current_advanced / $current_total) * 100) : 0;
                                    $growth = $current_advanced_percent - $initial_advanced_percent;
                                    $growth_class = $growth > 0 ? 'text-green-600 dark:text-green-400' : ($growth < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-400');
                                    $growth_icon = $growth > 0 ? '↑' : ($growth < 0 ? '↓' : '');
                                ?>
                                <tr>
                                    <td class="py-3 px-4 border-b border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 font-medium transition-colors duration-200"><?php echo htmlspecialchars($skill['skill_name']); ?></td>
                                    <td class="py-3 px-4 border-b border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 transition-colors duration-200"><?php echo ucfirst($skill['category']); ?></td>
                                    <td class="py-3 px-4 border-b border-gray-200 dark:border-gray-700 text-center text-gray-700 dark:text-gray-300 transition-colors duration-200"><?php echo $initial_basic; ?> (<?php echo $initial_total > 0 ? round(($initial_basic / $initial_total) * 100) : 0; ?>%)</td>
                                    <td class="py-3 px-4 border-b border-gray-200 dark:border-gray-700 text-center text-gray-700 dark:text-gray-300 transition-colors duration-200"><?php echo $initial_advanced; ?> (<?php echo $initial_advanced_percent; ?>%)</td>
                                    <td class="py-3 px-4 border-b border-gray-200 dark:border-gray-700 text-center text-gray-700 dark:text-gray-300 transition-colors duration-200"><?php echo $current_basic; ?> (<?php echo $current_total > 0 ? round(($current_basic / $current_total) * 100) : 0; ?>%)</td>
                                    <td class="py-3 px-4 border-b border-gray-200 dark:border-gray-700 text-center text-gray-700 dark:text-gray-300 transition-colors duration-200"><?php echo $current_advanced; ?> (<?php echo $current_advanced_percent; ?>%)</td>
                                    <td class="py-3 px-4 border-b border-gray-200 dark:border-gray-700 text-center <?php echo $growth_class; ?> font-semibold transition-colors duration-200"><?php echo $growth > 0 ? '+' : ''; ?><?php echo $growth; ?>% <?php echo $growth_icon; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
            <!-- Top Performing Interns -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-8 transition-colors duration-200">
                <h2 class="text-xl font-semibold mb-6 text-gray-700 dark:text-gray-300 flex items-center transition-colors duration-200">
                    <svg class="w-6 h-6 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    Top Performing Interns
                </h2>
                <?php if (empty($top_interns)): ?>
                    <p class="text-gray-600 dark:text-gray-400 transition-colors duration-200">No intern performance data available yet.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg transition-colors duration-200">
                            <thead>
                                <tr>
                                    <th class="py-2 px-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider transition-colors duration-200">Intern</th>
                                    <th class="py-2 px-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider transition-colors duration-200">Department</th>
                                    <th class="py-2 px-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider transition-colors duration-200">Advanced Skills</th>
                                    <th class="py-2 px-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider transition-colors duration-200">Proficiency</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_interns as $intern): ?>
                                    <tr>
                                        <td class="py-3 px-4 border-b border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 font-medium transition-colors duration-200"><?php echo htmlspecialchars($intern['first_name'] . ' ' . $intern['last_name']); ?></td>
                                        <td class="py-3 px-4 border-b border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 transition-colors duration-200"><?php echo htmlspecialchars($intern['department']); ?></td>
                                        <td class="py-3 px-4 border-b border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 transition-colors duration-200"><?php echo $intern['advanced_skills'] . '/' . $intern['total_skills']; ?></td>
                                        <td class="py-3 px-4 border-b border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 transition-colors duration-200">
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
            <!-- Skill Gaps -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-8 transition-colors duration-200">
                <h2 class="text-xl font-semibold mb-6 text-gray-700 dark:text-gray-300 flex items-center transition-colors duration-200">
                    <svg class="w-6 h-6 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-1.414 1.414A9 9 0 1112 3v0a9 9 0 016.364 2.636z" /></svg>
                    Skills with Largest Gaps
                </h2>
                <?php if (empty($skill_gaps)): ?>
                    <p class="text-gray-600 dark:text-gray-400 transition-colors duration-200">No skill gap data available yet.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach (array_slice($skill_gaps, 0, 10) as $skill): ?>
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <div>
                                        <span class="font-medium text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($skill['name']); ?></span>
                                        <span class="text-xs text-gray-500 ml-1">(<?php echo ucfirst($skill['category']); ?>)</span>
                                    </div>
                                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300"><?php echo $skill['beginners']; ?> beginners (<?php echo round($skill['beginner_percentage']); ?>%)</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="bg-red-500 h-2 rounded-full" style="width: <?php echo $skill['beginner_percentage']; ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Department Comparison -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-8 mb-10 transition-colors duration-200">
            <h2 class="text-xl font-semibold mb-6 text-gray-700 dark:text-gray-300 flex items-center transition-colors duration-200">
                <svg class="w-6 h-6 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v4a1 1 0 001 1h3m10-5h2a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V7a2 2 0 012-2h2" /></svg>
                Department Skill Comparison
            </h2>
            <?php
            // Prepare data for department comparison
            $department_data = [];
            
            $dept_query = $conn->query("SELECT DISTINCT department FROM users WHERE role = 'intern' AND department IS NOT NULL");
            $departments = $dept_query->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($departments as $dept_name) {
                $query = "
                    SELECT 
                        COUNT(DISTINCT CASE WHEN sa.current_level IN ('Advanced', 'Expert') THEN sa.user_id END) as advanced_users,
                        COUNT(DISTINCT sa.user_id) as total_users,
                        (COUNT(DISTINCT CASE WHEN sa.current_level IN ('Advanced', 'Expert') THEN sa.user_id END) / 
                        COUNT(DISTINCT sa.user_id)) * 100 as proficiency
                    FROM users u
                    LEFT JOIN skill_assessments sa ON u.id = sa.user_id
                    WHERE u.department = :department AND u.role = 'intern'
                ";
                
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':department', $dept_name);
                $stmt->execute();
                
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result) {
                    $department_data[] = [
                        'department' => $dept_name,
                        'proficiency' => round($result['proficiency'] ?? 0)
                    ];
                } else {
                    $department_data[] = [
                        'department' => $dept_name,
                        'proficiency' => 0
                    ];
                }
            }
            
            // Sort by proficiency (descending)
            usort($department_data, function($a, $b) {
                return $b['proficiency'] - $a['proficiency'];
            });
            ?>
            <?php if (empty($department_data)): ?>
                <p class="text-gray-600 dark:text-gray-400 transition-colors duration-200">No department comparison data available.</p>
            <?php else: ?>
                <div class="mb-8">
                    <canvas id="departmentComparisonChart" width="800" height="1"></canvas>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg transition-colors duration-200">
                        <thead>
                            <tr>
                                <th class="py-2 px-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider transition-colors duration-200">Department</th>
                                <th class="py-2 px-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider transition-colors duration-200">Overall Proficiency</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($department_data as $dept): ?>
                                <tr>
                                    <td class="py-3 px-4 border-b border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 font-medium transition-colors duration-200"><?php echo htmlspecialchars($dept['department']); ?></td>
                                    <td class="py-3 px-4 border-b border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 transition-colors duration-200">
                                        <div class="flex items-center">
                                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                                                <div class="bg-blue-500 h-2 rounded-full" style="width: <?php echo $dept['proficiency']; ?>%"></div>
                                            </div>
                                            <span class="text-sm font-medium text-gray-600 dark:text-gray-300"><?php echo $dept['proficiency']; ?>%</span>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($skill_growth_data)): ?>
    // Skill Growth Chart
    var skillGrowthData = <?php 
        $chart_data = [];
        foreach (array_slice($skill_growth_data, 0, 8) as $skill) {
            $chart_data[] = [
                'name' => $skill['skill_name'],
                'initialAdvanced' => ($skill['initial_advanced'] + $skill['initial_expert']),
                'currentAdvanced' => ($skill['current_advanced'] + $skill['current_expert']),
                'initialTotal' => ($skill['initial_beginner'] + $skill['initial_intermediate'] + $skill['initial_advanced'] + $skill['initial_expert']),
                'currentTotal' => ($skill['current_beginner'] + $skill['current_intermediate'] + $skill['current_advanced'] + $skill['current_expert'])
            ];
        }
        echo json_encode($chart_data);
    ?>;
    
    var ctx = document.getElementById('skillGrowthChart').getContext('2d');
    var skillGrowthChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: skillGrowthData.map(function(item) { return item.name; }),
            datasets: [
                {
                    label: 'Initial Advanced/Expert %',
                    data: skillGrowthData.map(function(item) { 
                        return item.initialTotal > 0 ? (item.initialAdvanced / item.initialTotal) * 100 : 0; 
                    }),
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Current Advanced/Expert %',
                    data: skillGrowthData.map(function(item) { 
                        return item.currentTotal > 0 ? (item.currentAdvanced / item.currentTotal) * 100 : 0; 
                    }),
                    backgroundColor: 'rgba(75, 192, 192, 0.5)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: 'Percentage of Interns at Advanced/Expert Level'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Skills'
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Skill Growth: Initial vs Current Proficiency'
                }
            }
        }
    });
    <?php endif; ?>
    
    <?php if (!empty($department_data)): ?>
    // Department Comparison Chart
    var departmentData = <?php echo json_encode($department_data); ?>;
    
    var deptCtx = document.getElementById('departmentComparisonChart').getContext('2d');
    var departmentComparisonChart = new Chart(deptCtx, {
        type: 'horizontalBar',
        data: {
            labels: departmentData.map(function(item) { return item.department; }),
            datasets: [
                {
                    label: 'Department Proficiency %',
                    data: departmentData.map(function(item) { return item.proficiency; }),
                    backgroundColor: 'rgba(153, 102, 255, 0.5)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            scales: {
                x: {
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: 'Overall Proficiency Percentage'
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Department Skill Proficiency Comparison'
                }
            }
        }
    });
    <?php endif; ?>
});
</script>

<?php
require_once "../includes/footer.php";
?>
