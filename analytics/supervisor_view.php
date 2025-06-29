<?php
require_once '../includes/header.php';
require_once '../config/database.php';

// Check if user is logged in and is a supervisor
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'supervisor') {
    header("Location: " . $base_path . "index.php");
    exit();
}

// Get database connection
$database = new Database();
$conn = $database->getConnection();

try {
    // Get all interns' progress data
    $stmt = $conn->prepare("
        SELECT 
            u.id as intern_id,
            CONCAT(u.first_name, ' ', u.last_name) as intern_name,
            COUNT(DISTINCT s.id) as total_skills,
            COUNT(DISTINCT CASE WHEN sa.current_level IN ('Advanced', 'Expert') THEN s.id END) as completed_skills,
            AVG(CASE 
                WHEN sa.current_level = 'Beginner' THEN 1
                WHEN sa.current_level = 'Intermediate' THEN 2
                WHEN sa.current_level = 'Advanced' THEN 3
                WHEN sa.current_level = 'Expert' THEN 4
                ELSE 0
            END) as avg_rating
        FROM users u
        LEFT JOIN skill_assessments sa ON u.id = sa.user_id
        LEFT JOIN skills s ON sa.skill_id = s.id
        WHERE u.role = 'intern'
        GROUP BY u.id, u.first_name, u.last_name
        ORDER BY avg_rating DESC, completed_skills DESC
    ");
    $stmt->execute();
    $interns_progress = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get skill category distribution
    $stmt = $conn->prepare("
        SELECT 
            s.category,
            COUNT(DISTINCT s.id) as total_skills,
            COUNT(DISTINCT CASE WHEN sa.current_level IN ('Advanced', 'Expert') THEN s.id END) as completed_skills
        FROM skills s
        LEFT JOIN skill_assessments sa ON s.id = sa.skill_id
        GROUP BY s.category
        ORDER BY s.category
    ");
    $stmt->execute();
    $category_progress = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate overall statistics
    $total_interns = count($interns_progress);
    $avg_completion = 0;
    $avg_rating = 0;
    $valid_interns = 0;

    foreach ($interns_progress as $intern) {
        if ($intern['total_skills'] > 0) {
            $avg_completion += ($intern['completed_skills'] / $intern['total_skills']) * 100;
            $avg_rating += $intern['avg_rating'];
            $valid_interns++;
        }
    }
    
    // Calculate averages only if there are valid interns with skills
    $avg_completion = $valid_interns > 0 ? round($avg_completion / $valid_interns, 1) : 0;
    $avg_rating = $valid_interns > 0 ? round($avg_rating / $valid_interns, 1) : 0;

} catch(PDOException $e) {
    $_SESSION['error'] = "Error fetching analytics data: " . $e->getMessage();
}
?>

<div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 dark:from-gray-900 dark:to-gray-800 py-12 transition-colors duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-extrabold text-gray-900 dark:text-white mb-3 transition-colors duration-300">Intern Progress Analytics</h1>
            <p class="text-lg text-gray-600 dark:text-gray-400 transition-colors duration-300">Track and analyze intern performance and skill development</p>
        </div>

        <!-- Overall Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8 transform transition duration-300 hover:scale-105 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 transition-colors duration-300">Total Interns</h3>
                    <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
                <p class="text-4xl font-bold text-blue-600 dark:text-blue-400 transition-colors duration-300"><?php echo $total_interns; ?></p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8 transform transition duration-300 hover:scale-105 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 transition-colors duration-300">Average Completion</h3>
                    <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <p class="text-4xl font-bold text-green-600 dark:text-green-400 transition-colors duration-300"><?php echo $avg_completion; ?>%</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8 transform transition duration-300 hover:scale-105 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 transition-colors duration-300">Average Rating</h3>
                    <div class="w-12 h-12 rounded-full bg-purple-100 dark:bg-purple-900 flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                        </svg>
                    </div>
                </div>
                <p class="text-4xl font-bold text-purple-600 dark:text-purple-400 transition-colors duration-300">
                    <?php
                    echo match(round($avg_rating)) {
                        1 => 'Beginner',
                        2 => 'Intermediate',
                        3 => 'Advanced',
                        4 => 'Expert',
                        default => 'Not Rated'
                    };
                    ?>
                </p>
            </div>
        </div>

        <!-- Intern Progress Table -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl overflow-hidden mb-12 border border-gray-200 dark:border-gray-700 transition-colors duration-300">
            <div class="p-6 border-b border-gray-200 dark:border-gray-600 bg-gradient-to-r from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700">
                <h2 class="text-xl font-semibold text-white transition-colors duration-300">Intern Progress Details</h2>
            </div>
            <?php if (empty($interns_progress)): ?>
                <div class="p-8 text-center">
                    <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-gray-600 dark:text-gray-400 text-lg transition-colors duration-300">No intern progress data available.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider transition-colors duration-300">Intern</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider transition-colors duration-300">Completed Skills</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider transition-colors duration-300">Progress</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider transition-colors duration-300">Average Rating</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider transition-colors duration-300">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                            <?php foreach ($interns_progress as $intern): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center mr-3">
                                            <span class="text-blue-600 dark:text-blue-400 font-semibold">
                                                <?php echo strtoupper(substr(explode(' ', $intern['intern_name'])[0], 0, 1) . substr(explode(' ', $intern['intern_name'])[1], 0, 1)); ?>
                                            </span>
                                        </div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white transition-colors duration-300"><?php echo htmlspecialchars($intern['intern_name']); ?></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white transition-colors duration-300">
                                        <?php echo $intern['completed_skills']; ?>/<?php echo $intern['total_skills']; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php 
                                    $progress = $intern['total_skills'] > 0 
                                        ? round(($intern['completed_skills'] / $intern['total_skills']) * 100) 
                                        : 0;
                                    ?>
                                    <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2.5">
                                        <div class="bg-blue-600 dark:bg-blue-400 h-2.5 rounded-full transition-all duration-300" style="width: <?php echo $progress; ?>%"></div>
                                    </div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 mt-1 transition-colors duration-300"><?php echo $progress; ?>%</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $rating = match(round($intern['avg_rating'])) {
                                        1 => ['label' => 'Beginner', 'color' => 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200'],
                                        2 => ['label' => 'Intermediate', 'color' => 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200'],
                                        3 => ['label' => 'Advanced', 'color' => 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200'],
                                        4 => ['label' => 'Expert', 'color' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200'],
                                        default => ['label' => 'Not Rated', 'color' => 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200']
                                    };
                                    ?>
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $rating['color']; ?> transition-colors duration-300">
                                        <?php echo $rating['label']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $status = match(true) {
                                        $progress >= 80 => ['label' => 'Excellent', 'color' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200'],
                                        $progress >= 50 => ['label' => 'Good', 'color' => 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200'],
                                        default => ['label' => 'Needs Improvement', 'color' => 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200']
                                    };
                                    ?>
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status['color']; ?> transition-colors duration-300">
                                        <?php echo $status['label']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Skill Category Progress -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8 border border-gray-200 dark:border-gray-700 transition-colors duration-300">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white transition-colors duration-300">Skill Category Progress</h2>
                <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
            </div>
            <?php if (empty($category_progress)): ?>
                <div class="text-center py-8">
                    <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <p class="text-gray-600 dark:text-gray-400 text-lg transition-colors duration-300">No skill category data available.</p>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($category_progress as $category): ?>
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-2xl p-6 border border-gray-200 dark:border-gray-600 transition-colors duration-300">
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-lg font-medium text-gray-900 dark:text-white transition-colors duration-300"><?php echo ucfirst($category['category']); ?></span>
                            <span class="text-lg font-medium text-gray-900 dark:text-white transition-colors duration-300">
                                <?php 
                                $progress = $category['total_skills'] > 0 
                                    ? round(($category['completed_skills'] / $category['total_skills']) * 100) 
                                    : 0;
                                echo $progress . '%';
                                ?>
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-3">
                            <div class="bg-blue-600 dark:bg-blue-400 h-3 rounded-full transition-all duration-300" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                        <div class="flex justify-between mt-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400 transition-colors duration-300">
                                <?php echo $category['completed_skills']; ?>/<?php echo $category['total_skills']; ?> skills completed
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 