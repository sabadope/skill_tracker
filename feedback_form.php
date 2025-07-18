<?php
session_start();
require_once 'config/database.php';

// Get user_id and role from URL parameters
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$role = isset($_GET['role']) ? $_GET['role'] : '';

// Validate user exists and is an intern
if (!$user_id || $role !== 'intern') {
    die('Invalid feedback link');
}

// Get database connection
$database = new Database();
$conn = $database->getConnection();

// Fetch intern information
$stmt = $conn->prepare("SELECT first_name, last_name, department FROM users WHERE id = ? AND role = 'intern'");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$intern = $result->fetch_assoc();

if (!$intern) {
    die('Intern not found');
}

$success = $error = '';

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = trim($_POST['customer_name']);
    $service_rating = intval($_POST['service_rating']);
    $skill_rating = intval($_POST['skill_rating']);
    $communication_rating = intval($_POST['communication_rating']);
    $overall_rating = intval($_POST['overall_rating']);
    $comments = trim($_POST['comments']);
    
    // Validate inputs
    if (empty($customer_name)) {
        $error = 'Please provide your name';
    } elseif ($service_rating < 1 || $service_rating > 5) {
        $error = 'Please provide a valid service rating';
    } elseif ($skill_rating < 1 || $skill_rating > 5) {
        $error = 'Please provide a valid skill rating';
    } elseif ($communication_rating < 1 || $communication_rating > 5) {
        $error = 'Please provide a valid communication rating';
    } elseif ($overall_rating < 1 || $overall_rating > 5) {
        $error = 'Please provide a valid overall rating';
    } else {
        // Insert feedback into database
        $stmt = $conn->prepare("INSERT INTO customer_feedback (intern_id, customer_name, service_rating, skill_rating, communication_rating, overall_rating, comments, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param('isiiiss', $user_id, $customer_name, $service_rating, $skill_rating, $communication_rating, $overall_rating, $comments);
        
        if ($stmt->execute()) {
            $success = 'Thank you for your feedback!';
        } else {
            $error = 'Failed to submit feedback. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback for <?php echo htmlspecialchars($intern['first_name'] . ' ' . $intern['last_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            300: '#cbd5e1',
                            400: '#94a3b8',
                            500: '#64748b',
                            600: '#475569',
                            700: '#334155',
                            800: '#1e293b',
                            900: '#0f172a',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900 dark:text-white">
                    Feedback Form
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Please provide feedback for
                </p>
                <p class="text-lg font-semibold text-blue-600 dark:text-blue-400">
                    <?php echo htmlspecialchars($intern['first_name'] . ' ' . $intern['last_name']); ?>
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-500">
                    Department: <?php echo htmlspecialchars($intern['department']); ?>
                </p>
            </div>

            <?php if ($success): ?>
                <div class="bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo $success; ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <?php if (!$success): ?>
            <form class="mt-8 space-y-6" method="POST">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                    <div class="space-y-6">
                        <!-- Customer Name -->
                        <div>
                            <label for="customer_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Your Name *
                            </label>
                            <input type="text" name="customer_name" id="customer_name" required
                                   class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 focus:z-10 sm:text-sm"
                                   placeholder="Enter your name">
                        </div>

                        <!-- Service Rating -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Service Quality *
                            </label>
                            <div class="flex space-x-4">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <label class="flex items-center">
                                        <input type="radio" name="service_rating" value="<?php echo $i; ?>" required
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300"><?php echo $i; ?></span>
                                    </label>
                                <?php endfor; ?>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">1 = Poor, 5 = Excellent</p>
                        </div>

                        <!-- Skill Rating -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Technical Skills *
                            </label>
                            <div class="flex space-x-4">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <label class="flex items-center">
                                        <input type="radio" name="skill_rating" value="<?php echo $i; ?>" required
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300"><?php echo $i; ?></span>
                                    </label>
                                <?php endfor; ?>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">1 = Poor, 5 = Excellent</p>
                        </div>

                        <!-- Communication Rating -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Communication Skills *
                            </label>
                            <div class="flex space-x-4">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <label class="flex items-center">
                                        <input type="radio" name="communication_rating" value="<?php echo $i; ?>" required
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300"><?php echo $i; ?></span>
                                    </label>
                                <?php endfor; ?>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">1 = Poor, 5 = Excellent</p>
                        </div>

                        <!-- Overall Rating -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Overall Experience *
                            </label>
                            <div class="flex space-x-4">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <label class="flex items-center">
                                        <input type="radio" name="overall_rating" value="<?php echo $i; ?>" required
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300"><?php echo $i; ?></span>
                                    </label>
                                <?php endfor; ?>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">1 = Poor, 5 = Excellent</p>
                        </div>

                        <!-- Comments -->
                        <div>
                            <label for="comments" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Additional Comments
                            </label>
                            <textarea name="comments" id="comments" rows="4"
                                      class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 focus:z-10 sm:text-sm"
                                      placeholder="Share your experience and suggestions..."></textarea>
                        </div>
                    </div>

                    <div class="mt-6">
                        <button type="submit"
                                class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Submit Feedback
                        </button>
                    </div>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
 