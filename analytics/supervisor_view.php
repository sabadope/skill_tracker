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
    foreach ($interns_progress as $intern) {
        $avg_completion += ($intern['completed_skills'] / $intern['total_skills']) * 100;
        $avg_rating += $intern['avg_rating'];
    }
    $avg_completion = $total_interns > 0 ? round($avg_completion / $total_interns, 1) : 0;
    $avg_rating = $total_interns > 0 ? round($avg_rating / $total_interns, 1) : 0;

} catch(PDOException $e) {
    $_SESSION['error'] = "Error fetching analytics data: " . $e->getMessage();
}
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Intern Progress Analytics</h1>

    <!-- Overall Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Total Interns</h3>
            <p class="text-3xl font-bold text-blue-600"><?php echo $total_interns; ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Average Completion</h3>
            <p class="text-3xl font-bold text-green-600"><?php echo $avg_completion; ?>%</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Average Rating</h3>
            <p class="text-3xl font-bold text-purple-600">
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
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">Intern Progress Details</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full table-auto">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Intern</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completed Skills</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Average Rating</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($interns_progress as $intern): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($intern['intern_name']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?php echo $intern['completed_skills']; ?>/<?php echo $intern['total_skills']; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php 
                            $progress = $intern['total_skills'] > 0 
                                ? round(($intern['completed_skills'] / $intern['total_skills']) * 100) 
                                : 0;
                            ?>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="bg-blue-600 h-2.5 rounded-full" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                            <span class="text-xs text-gray-500 mt-1"><?php echo $progress; ?>%</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $rating = match(round($intern['avg_rating'])) {
                                1 => ['label' => 'Beginner', 'color' => 'bg-gray-100 text-gray-800'],
                                2 => ['label' => 'Intermediate', 'color' => 'bg-yellow-100 text-yellow-800'],
                                3 => ['label' => 'Advanced', 'color' => 'bg-blue-100 text-blue-800'],
                                4 => ['label' => 'Expert', 'color' => 'bg-green-100 text-green-800'],
                                default => ['label' => 'Not Rated', 'color' => 'bg-gray-100 text-gray-800']
                            };
                            ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $rating['color']; ?>">
                                <?php echo $rating['label']; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $status = match(true) {
                                $progress >= 80 => ['label' => 'Excellent', 'color' => 'bg-green-100 text-green-800'],
                                $progress >= 50 => ['label' => 'Good', 'color' => 'bg-blue-100 text-blue-800'],
                                default => ['label' => 'Needs Improvement', 'color' => 'bg-yellow-100 text-yellow-800']
                            };
                            ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status['color']; ?>">
                                <?php echo $status['label']; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Skill Category Progress -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4">Skill Category Progress</h2>
        <div class="space-y-4">
            <?php foreach ($category_progress as $category): ?>
            <div>
                <div class="flex justify-between mb-1">
                    <span class="text-sm font-medium text-gray-700"><?php echo ucfirst($category['category']); ?></span>
                    <span class="text-sm font-medium text-gray-700">
                        <?php 
                        $progress = $category['total_skills'] > 0 
                            ? round(($category['completed_skills'] / $category['total_skills']) * 100) 
                            : 0;
                        echo $progress . '%';
                        ?>
                    </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div class="bg-blue-600 h-2.5 rounded-full" style="width: <?php echo $progress; ?>%"></div>
                </div>
                <div class="flex justify-between mt-1">
                    <span class="text-xs text-gray-500">
                        <?php echo $category['completed_skills']; ?>/<?php echo $category['total_skills']; ?> skills completed
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 