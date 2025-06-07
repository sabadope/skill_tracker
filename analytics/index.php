<?php
require_once '../includes/header.php';
require_once '../config/database.php';

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
            COUNT(DISTINCT CASE WHEN sa.current_level IN ('Advanced', 'Expert') THEN s.id END) as completed_skills
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

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">My Progress Analytics</h1>

    <!-- Overall Progress Card -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">Overall Progress</h2>
        <div class="flex items-center justify-between">
            <div class="w-3/4">
                <div class="relative pt-1">
                    <div class="flex mb-2 items-center justify-between">
                        <div>
                            <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-blue-600 bg-blue-200">
                                Progress
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-semibold inline-block text-blue-600">
                                <?php echo $progress_percentage; ?>%
                            </span>
                        </div>
                    </div>
                    <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-blue-200">
                        <div style="width:<?php echo $progress_percentage; ?>%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-blue-500"></div>
                    </div>
                </div>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-600">Advanced/Expert Skills</p>
                <p class="text-2xl font-bold text-blue-600">
                    <?php echo $overall_progress['completed_skills']; ?>/<?php echo $overall_progress['total_skills']; ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Skill Progress Table -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4">Skill Progress Details</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full table-auto">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Skill</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Your Level</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supervisor Rating</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Updated</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($skill_progress as $skill): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($skill['skill_name']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-500"><?php echo ucfirst(htmlspecialchars($skill['category'])); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($skill['rating']): ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php
                                    $color = match($skill['rating']) {
                                        'Beginner' => 'bg-gray-100 text-gray-800',
                                        'Intermediate' => 'bg-yellow-100 text-yellow-800',
                                        'Advanced' => 'bg-blue-100 text-blue-800',
                                        'Expert' => 'bg-green-100 text-green-800',
                                        default => 'bg-gray-100 text-gray-800'
                                    };
                                    echo $color;
                                    ?>">
                                    <?php echo $skill['rating']; ?>
                                </span>
                            <?php else: ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                    Not Started
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($skill['supervisor_rating']): ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php
                                    $color = match($skill['supervisor_rating']) {
                                        'Beginner' => 'bg-gray-100 text-gray-800',
                                        'Intermediate' => 'bg-yellow-100 text-yellow-800',
                                        'Advanced' => 'bg-blue-100 text-blue-800',
                                        'Expert' => 'bg-green-100 text-green-800',
                                        default => 'bg-gray-100 text-gray-800'
                                    };
                                    echo $color;
                                    ?>">
                                    <?php echo $skill['supervisor_rating']; ?>
                                </span>
                            <?php else: ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                    Not Rated
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-500">
                                <?php echo $skill['evaluation_date'] ? date('M d, Y', strtotime($skill['evaluation_date'])) : 'Not Updated'; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($skill['rating'] === 'Expert'): ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Mastered
                                </span>
                            <?php elseif ($skill['rating'] === 'Advanced'): ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    Advanced
                                </span>
                            <?php elseif ($skill['rating'] === 'Intermediate'): ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    In Progress
                                </span>
                            <?php elseif ($skill['rating'] === 'Beginner'): ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                    Beginner
                                </span>
                            <?php else: ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
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

<?php require_once '../includes/footer.php'; ?> 