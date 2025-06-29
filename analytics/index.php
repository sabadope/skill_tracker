<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Redirect based on user role
if ($_SESSION['user_role'] === 'supervisor') {
    header("Location: ./supervisor_view.php");
    exit();
}

require_once '../includes/header.php';

// Get user's role and ID
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Get database connection
$database = new Database();
$conn = $database->getConnection();

// Get skill progress data
try {
    $stmt = $conn->prepare("
        SELECT 
            s.name as skill_name,
            s.category,
            sa.current_level as rating,
            sa.last_updated as evaluation_date,
            sa.supervisor_comments as comments,
            sa.supervisor_rating,
            CONCAT(u.first_name, ' ', u.last_name) as evaluator_name
        FROM skills s
        LEFT JOIN skill_assessments sa ON s.id = sa.skill_id AND sa.user_id = :user_id
        LEFT JOIN users u ON sa.supervisor_id = u.id
        ORDER BY s.category, s.name
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $skill_progress = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get overall progress
    $stmt = $conn->prepare("
        SELECT 
            COUNT(DISTINCT s.id) as total_skills,
            COUNT(DISTINCT CASE WHEN sa.supervisor_rating IN ('Advanced', 'Expert') THEN s.id END) as completed_skills
        FROM skills s
        LEFT JOIN skill_assessments sa ON s.id = sa.skill_id AND sa.user_id = :user_id
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $overall_progress = $stmt->fetch(PDO::FETCH_ASSOC);

    // Calculate progress percentage
    $progress_percentage = $overall_progress['total_skills'] > 0 
        ? round(($overall_progress['completed_skills'] / $overall_progress['total_skills']) * 100) 
        : 0;

} catch(PDOException $e) {
    $_SESSION['error'] = "Error fetching progress data: " . $e->getMessage();
}
?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 transition-colors duration-200">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white mb-10 text-center transition-colors duration-200">My Progress Analytics</h1>

        <!-- Overall Progress Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 mb-10 transition-colors duration-200">
            <h2 class="text-xl font-semibold mb-6 text-gray-700 dark:text-gray-300 flex items-center transition-colors duration-200">
                <svg class="w-6 h-6 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                Overall Progress
            </h2>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div class="w-full md:w-3/4">
                    <div class="relative pt-1">
                        <div class="flex mb-2 items-center justify-between">
                            <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900 transition-colors duration-200">Progress</span>
                            <span class="text-xs font-semibold inline-block text-blue-600 dark:text-blue-400 transition-colors duration-200"><?php echo $progress_percentage; ?>%</span>
                        </div>
                        <div class="overflow-hidden h-4 mb-4 text-xs flex rounded-full bg-blue-100 dark:bg-blue-900 transition-colors duration-200">
                            <div style="width:<?php echo $progress_percentage; ?>%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-blue-500 transition-all duration-500"></div>
                        </div>
                    </div>
                </div>
                <div class="text-right mt-4 md:mt-0">
                    <p class="text-sm text-gray-600 dark:text-gray-400 transition-colors duration-200">Advanced/Expert Skills</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 transition-colors duration-200"><?php echo $overall_progress['completed_skills']; ?>/<?php echo $overall_progress['total_skills']; ?></p>
                </div>
            </div>
        </div>

        <!-- Skill Progress Table -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 transition-colors duration-200">
            <h2 class="text-xl font-semibold mb-6 text-gray-700 dark:text-gray-300 flex items-center transition-colors duration-200">
                <svg class="w-6 h-6 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                Skill Progress Details
            </h2>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg transition-colors duration-200">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider transition-colors duration-200">Skill</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider transition-colors duration-200">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider transition-colors duration-200">Your Level</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider transition-colors duration-200">Supervisor Rating</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider transition-colors duration-200">Last Updated</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider transition-colors duration-200">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($skill_progress as $skill): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white transition-colors duration-200"><?php echo htmlspecialchars($skill['skill_name']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500 dark:text-gray-400 transition-colors duration-200"><?php echo ucfirst(htmlspecialchars($skill['category'])); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($skill['rating']): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php
                                        $color = match($skill['rating']) {
                                            'Beginner' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                                            'Intermediate' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                            'Advanced' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                            'Expert' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                            default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
                                        };
                                        echo $color;
                                        ?> transition-colors duration-200">
                                        <?php echo $skill['rating']; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200 transition-colors duration-200">
                                        Not Started
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($skill['supervisor_rating']): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php
                                        $color = match($skill['supervisor_rating']) {
                                            'Beginner' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                                            'Intermediate' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                            'Advanced' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                            'Expert' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                            default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
                                        };
                                        echo $color;
                                        ?> transition-colors duration-200">
                                        <?php echo $skill['supervisor_rating']; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200 transition-colors duration-200">
                                        Not Rated
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500 dark:text-gray-400 transition-colors duration-200">
                                    <?php echo $skill['evaluation_date'] ? date('M d, Y', strtotime($skill['evaluation_date'])) : 'Not Updated'; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($skill['supervisor_rating'] === 'Expert'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 transition-colors duration-200">
                                        Mastered
                                    </span>
                                <?php elseif ($skill['supervisor_rating'] === 'Advanced'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 transition-colors duration-200">
                                        Advanced
                                    </span>
                                <?php elseif ($skill['supervisor_rating'] === 'Intermediate'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 transition-colors duration-200">
                                        In Progress
                                    </span>
                                <?php elseif ($skill['supervisor_rating'] === 'Beginner'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200 transition-colors duration-200">
                                        Beginner
                                    </span>
                                <?php elseif ($skill['rating']): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200 transition-colors duration-200">
                                        Pending Review
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200 transition-colors duration-200">
                                        Not Started
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 