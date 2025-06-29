<?php
// Supervisor skill evaluation page
require_once "../config/database.php";
require_once "../includes/auth.php";
require_once "../includes/functions.php";

// Check if user is logged in and is a supervisor (or admin in view mode)
require_role_with_admin_view('supervisor');

// Check if admin is in view mode
$is_admin_view_mode = is_admin_in_view_mode();
$admin_view_mode = get_admin_view_mode();
$selected_user_id = get_admin_selected_user_id();

// Get database connection
$database = new Database();
$conn = $database->getConnection();

// Get supervisor ID
$supervisor_id = $_SESSION['user_id'];

// Get all interns
$interns = get_users_by_role($conn, 'intern');

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Only keep skill evaluation logic
    if (isset($_POST['evaluate_skill'])) {
        // Process skill evaluation
        $intern_id = isset($_POST['intern_id']) ? (int)$_POST['intern_id'] : 0;
        $skill_id = isset($_POST['skill_id']) ? (int)$_POST['skill_id'] : 0;
        $rating = isset($_POST['rating']) ? sanitize_input($_POST['rating']) : '';
        $comments = isset($_POST['comments']) ? sanitize_input($_POST['comments']) : '';
        
        // Debug logging
        error_log("=== EVALUATE SKILL DEBUG ===");
        error_log("Intern ID: $intern_id");
        error_log("Skill ID: $skill_id");
        error_log("Rating: $rating");
        error_log("Comments: $comments");
        
        // Validate form data
        $errors = [];
        if (empty($intern_id) || empty($skill_id)) {
            $errors[] = "Intern and skill are required";
        }
        
        if (empty($rating)) {
            $errors[] = "Rating is required";
        } elseif (!in_array($rating, ['Beginner', 'Intermediate', 'Advanced', 'Expert'])) {
            $errors[] = "Invalid rating";
        }
        
        // If no errors, update the evaluation
        if (empty($errors)) {
            $result = evaluate_intern_skill($conn, $intern_id, $skill_id, $supervisor_id, $rating, $comments);
            error_log("Evaluation result: " . ($result ? 'SUCCESS' : 'FAILED'));
            
            if ($result) {
                $_SESSION['success'] = "Skill evaluation submitted successfully";
                error_log("SUCCESS: Skill evaluation submitted");
            } else {
                $_SESSION['error'] = "Failed to submit evaluation";
                error_log("ERROR: Failed to submit evaluation");
            }
        } else {
            $_SESSION['error'] = implode(", ", $errors);
            error_log("VALIDATION ERRORS: " . implode(", ", $errors));
        }
        error_log("=== EVALUATE SKILL DEBUG END ===");
    }
}

// Get selected intern if provided in URL
$selected_intern_id = isset($_GET['intern_id']) ? (int)$_GET['intern_id'] : (isset($_POST['intern_id']) ? (int)$_POST['intern_id'] : 0);
$selected_intern = null;
$intern_skills = [];

if ($selected_intern_id > 0) {
    $selected_intern = get_user_by_id($conn, $selected_intern_id);
    if ($selected_intern) {
        $intern_skills = get_user_skills($conn, $selected_intern_id);
        
        // Debug logging for skills retrieval
        error_log("=== SKILLS RETRIEVAL DEBUG ===");
        error_log("Intern ID: $selected_intern_id");
        error_log("Intern Name: " . $selected_intern['first_name'] . " " . $selected_intern['last_name']);
        error_log("Total skills found: " . count($intern_skills));
        
        foreach ($intern_skills as $skill) {
            error_log("Skill: " . $skill['skill_name'] . " (ID: " . $skill['skill_id'] . ", Category: " . $skill['skill_category'] . ")");
        }
        error_log("=== SKILLS RETRIEVAL DEBUG END ===");
    }
}

// Get all skills for task assignment
$all_skills = get_all_skills($conn);

// Organize skills by category
$technical_skills = array_filter($intern_skills, function($skill) {
    return $skill['skill_category'] === 'technical';
});

$soft_skills = array_filter($intern_skills, function($skill) {
    return $skill['skill_category'] === 'soft';
});

$other_skills = array_filter($intern_skills, function($skill) {
    return $skill['skill_category'] === 'other';
});

// Debug logging for skill categorization
error_log("=== SKILL CATEGORIZATION DEBUG ===");
error_log("Technical skills: " . count($technical_skills));
error_log("Soft skills: " . count($soft_skills));
error_log("Other skills: " . count($other_skills));
error_log("=== SKILL CATEGORIZATION DEBUG END ===");

// Determine if an edit is requested for a specific skill
$edit_skill_id = isset($_POST['edit_skill_id']) ? (int)$_POST['edit_skill_id'] : 0;

// Include header
require_once "../includes/header.php";
?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 transition-colors duration-300">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php include_once "../includes/admin_view_banner.php"; ?>

        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white mb-10 text-center">
            <?php if ($is_admin_view_mode): ?>
                Skill Evaluation (Read-Only View)
            <?php else: ?>
                Skill Evaluation
            <?php endif; ?>
        </h1>

        <!-- Intern Selection -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 mb-10 transition-colors duration-200">
            <h2 class="text-xl font-semibold mb-6 text-gray-700 dark:text-gray-300 transition-colors duration-200">Select Intern</h2>
            <form method="GET" action="evaluate_skill.php">
                <div class="flex flex-col md:flex-row md:items-end space-y-4 md:space-y-0 md:space-x-4">
                    <div class="flex-grow">
                        <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 transition-colors duration-200" for="intern_id">Intern</label>
                        <select name="intern_id" id="intern_id" class="appearance-none rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white w-full py-2 px-3 text-gray-700 dark:text-gray-300 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200" required>
                            <option value="">-- Select an intern --</option>
                            <?php foreach ($interns as $intern): ?>
                                <option value="<?php echo $intern['id']; ?>" <?php echo ($selected_intern_id == $intern['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($intern['first_name'] . ' ' . $intern['last_name'] . ' (' . $intern['department'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg shadow transition duration-200">
                            View Skills
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <?php if ($selected_intern): ?>
        <!-- Intern Details -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 mb-10 transition-colors duration-200">
            <div class="flex flex-col md:flex-row justify-between">
                <div>
                    <h2 class="text-xl font-semibold mb-2 text-gray-700 dark:text-gray-300 transition-colors duration-200"><?php echo htmlspecialchars($selected_intern['first_name'] . ' ' . $selected_intern['last_name']); ?></h2>
                    <p class="text-gray-600 dark:text-gray-400 transition-colors duration-200">Department: <?php echo htmlspecialchars($selected_intern['department']); ?></p>
                    <p class="text-gray-600 dark:text-gray-400 transition-colors duration-200">Email: <?php echo htmlspecialchars($selected_intern['email']); ?></p>
                </div>
            </div>
        </div>

        <!-- Technical Skills -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 mb-10 transition-colors duration-200">
            <h2 class="text-xl font-semibold mb-6 text-gray-700 dark:text-gray-300 transition-colors duration-200">Technical Skills</h2>
            <?php if (empty($technical_skills)): ?>
                <p class="text-gray-600 dark:text-gray-400 transition-colors duration-200">No technical skills added yet.</p>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($technical_skills as $skill): ?>
                        <div class="border rounded-2xl p-6 shadow">
                            <div class="flex flex-col md:flex-row md:justify-between md:items-start mb-4">
                                <div>
                                    <h3 class="font-semibold text-lg text-gray-800 dark:text-white transition-colors duration-200"><?php echo htmlspecialchars($skill['skill_name']); ?></h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 transition-colors duration-200"><?php echo htmlspecialchars($skill['skill_description']); ?></p>
                                </div>
                                <div class="mt-2 md:mt-0 flex flex-col md:items-end">
                                    <div class="mb-2">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300 transition-colors duration-200">Intern Assessment:</span>
                                        <?php 
                                            $level_color = get_level_color($skill['current_level']);
                                            $bg_color = "bg-{$level_color}-100";
                                            $text_color = "text-{$level_color}-800";
                                        ?>
                                        <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full bg-<?php echo $level_color; ?>-300 dark:bg-<?php echo $level_color; ?>-700 text-<?php echo $level_color; ?>-800 dark:text-<?php echo $level_color; ?>-200">
                                            <?php echo $skill['current_level']; ?>
                                        </span>
                                    </div>
                                    <?php if ($skill['supervisor_rating']): ?>
                                    <div>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300 transition-colors duration-200">Your Evaluation:</span>
                                        <?php 
                                            $sup_color = get_level_color($skill['supervisor_rating']);
                                            $sup_bg_color = "bg-{$sup_color}-100";
                                            $sup_text_color = "text-{$sup_color}-800";
                                        ?>
                                        <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full bg-<?php echo $sup_color; ?>-500 dark:bg-<?php echo $sup_color; ?>-400 text-<?php echo $sup_color; ?>-100 dark:text-<?php echo $sup_color; ?>-900">
                                            <?php echo $skill['supervisor_rating']; ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="mb-4">
                                <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                                    <br>
                                </div>
                                <!-- Static level scale -->
                                <div class="flex justify-between text-xxs text-gray-400 dark:text-gray-500 mb-1">
                                    <span>Beginner</span>
                                    <span>Intermediate</span>
                                    <span>Advanced</span>
                                    <span>Expert</span>
                                </div>
                                <div class="relative h-3 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                                    <?php 
                                        $intern_progress = calculate_level_progress($skill['current_level']);
                                        $supervisor_progress = $skill['supervisor_rating'] ? calculate_level_progress($skill['supervisor_rating']) : 0;
                                        $intern_color = get_level_color($skill['current_level']);
                                        $supervisor_color = $skill['supervisor_rating'] ? get_level_color($skill['supervisor_rating']) : '';
                                    ?>
                                    <!-- Supervisor assessment (base, full color, supervisor's color) -->
                                    <?php if ($skill['supervisor_rating']): ?>
                                    <div class="absolute h-3 bg-<?php echo $supervisor_color; ?>-500 dark:bg-<?php echo $supervisor_color; ?>-400 rounded-full" style="width: <?php echo $supervisor_progress; ?>%"></div>
                                    <?php endif; ?>
                                    <!-- Intern assessment (overlay, lighter color, intern's color) -->
                                    <div class="absolute h-3 bg-<?php echo $intern_color; ?>-300 dark:bg-<?php echo $intern_color; ?>-700 rounded-full" style="width: <?php echo $intern_progress; ?>%"></div>
                                </div>
                            </div>
                            <?php if ($skill['supervisor_comments']): ?>
                                <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-md border border-gray-200 dark:border-gray-600">
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 transition-colors duration-200">Your Feedback:</p>
                                    <p class="text-gray-600 dark:text-gray-400 italic">"<?php echo htmlspecialchars($skill['supervisor_comments']); ?>"</p>
                                </div>
                            <?php endif; ?>
                            <?php if ($skill['supervisor_rating'] && $edit_skill_id !== $skill['skill_id']): ?>
                                <!-- Show summary and Edit button -->
                                <div class="flex justify-end">
                                    <?php if (!$is_admin_view_mode): ?>
                                    <form action="evaluate_skill.php" method="POST">
                                        <input type="hidden" name="intern_id" value="<?php echo $selected_intern_id; ?>">
                                        <input type="hidden" name="edit_skill_id" value="<?php echo $skill['skill_id']; ?>">
                                        <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-6 rounded-lg shadow transition duration-200">Edit Evaluation</button>
                                    </form>
                                    <?php else: ?>
                                    <div class="bg-gray-400 dark:bg-gray-600 text-white font-bold py-2 px-6 rounded-lg shadow cursor-not-allowed opacity-50">
                                        Edit Evaluation (Read-Only)
                                    </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <!-- Show the form for new or editing -->
                                <?php if (!$is_admin_view_mode): ?>
                                <form action="evaluate_skill.php" method="POST" class="mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-md border border-gray-200 dark:border-gray-600">
                                    <input type="hidden" name="evaluate_skill" value="1">
                                    <input type="hidden" name="intern_id" value="<?php echo $selected_intern_id; ?>">
                                    <input type="hidden" name="skill_id" value="<?php echo $skill['skill_id']; ?>">
                                    <div class="mb-4">
                                        <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 transition-colors duration-200" for="rating_<?php echo $skill['skill_id']; ?>">Your Evaluation</label>
                                        <select name="rating" id="rating_<?php echo $skill['skill_id']; ?>" class="appearance-none rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white w-full py-2 px-3 text-gray-700 dark:text-gray-300 leading-tight focus:outline-none focus:ring-2 focus:ring-purple-500 transition-colors duration-200" required>
                                            <option value="">-- Select your evaluation --</option>
                                            <option value="Beginner" <?php echo ($skill['supervisor_rating'] === 'Beginner') ? 'selected' : ''; ?>>Beginner</option>
                                            <option value="Intermediate" <?php echo ($skill['supervisor_rating'] === 'Intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                                            <option value="Advanced" <?php echo ($skill['supervisor_rating'] === 'Advanced') ? 'selected' : ''; ?>>Advanced</option>
                                            <option value="Expert" <?php echo ($skill['supervisor_rating'] === 'Expert') ? 'selected' : ''; ?>>Expert</option>
                                        </select>
                                    </div>
                                    <div class="mb-4">
                                        <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 transition-colors duration-200" for="comments_<?php echo $skill['skill_id']; ?>">Feedback Comments</label>
                                        <textarea name="comments" id="comments_<?php echo $skill['skill_id']; ?>" rows="3" class="appearance-none rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white w-full py-2 px-3 text-gray-700 dark:text-gray-300 leading-tight focus:outline-none focus:ring-2 focus:ring-purple-500 transition-colors duration-200" placeholder="Provide constructive feedback..."><?php echo htmlspecialchars($skill['supervisor_comments'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="flex justify-end">
                                        <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-6 rounded-lg shadow transition duration-200">
                                            <?php echo $skill['supervisor_rating'] ? 'Update Evaluation' : 'Submit Evaluation'; ?>
                                        </button>
                                    </div>
                                </form>
                                <?php else: ?>
                                <div class="mt-4 p-4 bg-gray-100 dark:bg-gray-800 rounded-md border border-gray-300 dark:border-gray-600">
                                    <div class="mb-4">
                                        <label class="block text-gray-500 dark:text-gray-400 text-sm font-bold mb-2">Your Evaluation (Read-Only)</label>
                                        <div class="appearance-none rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white w-full py-2 px-3 text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800">
                                            <?php echo $skill['supervisor_rating'] ? htmlspecialchars($skill['supervisor_rating']) : 'Not evaluated yet'; ?>
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label class="block text-gray-500 dark:text-gray-400 text-sm font-bold mb-2">Feedback Comments (Read-Only)</label>
                                        <div class="appearance-none rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white w-full py-2 px-3 text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 min-h-[60px]">
                                            <?php echo $skill['supervisor_comments'] ? htmlspecialchars($skill['supervisor_comments']) : 'No comments provided'; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Soft Skills -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 mb-10 transition-colors duration-200">
            <h2 class="text-xl font-semibold mb-6 text-gray-700 dark:text-gray-300 transition-colors duration-200">Soft Skills</h2>
            <?php if (empty($soft_skills)): ?>
                <p class="text-gray-600 dark:text-gray-400 transition-colors duration-200">No soft skills added yet.</p>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($soft_skills as $skill): ?>
                        <div class="border rounded-2xl p-6 shadow">
                            <div class="flex flex-col md:flex-row md:justify-between md:items-start mb-4">
                                <div>
                                    <h3 class="font-semibold text-lg text-gray-800 dark:text-white transition-colors duration-200"><?php echo htmlspecialchars($skill['skill_name']); ?></h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 transition-colors duration-200"><?php echo htmlspecialchars($skill['skill_description']); ?></p>
                                </div>
                                <div class="mt-2 md:mt-0 flex flex-col md:items-end">
                                    <div class="mb-2">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300 transition-colors duration-200">Intern Assessment:</span>
                                        <?php 
                                            $level_color = get_level_color($skill['current_level']);
                                            $bg_color = "bg-{$level_color}-100";
                                            $text_color = "text-{$level_color}-800";
                                        ?>
                                        <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full bg-<?php echo $level_color; ?>-300 dark:bg-<?php echo $level_color; ?>-700 text-<?php echo $level_color; ?>-800 dark:text-<?php echo $level_color; ?>-200">
                                            <?php echo $skill['current_level']; ?>
                                        </span>
                                    </div>
                                    <?php if ($skill['supervisor_rating']): ?>
                                    <div>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300 transition-colors duration-200">Your Evaluation:</span>
                                        <?php 
                                            $sup_color = get_level_color($skill['supervisor_rating']);
                                            $sup_bg_color = "bg-{$sup_color}-100";
                                            $sup_text_color = "text-{$sup_color}-800";
                                        ?>
                                        <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full bg-<?php echo $sup_color; ?>-500 dark:bg-<?php echo $sup_color; ?>-400 text-<?php echo $sup_color; ?>-100 dark:text-<?php echo $sup_color; ?>-900">
                                            <?php echo $skill['supervisor_rating']; ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="mb-4">
                                <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                                    <br>
                                </div>
                                <!-- Static level scale -->
                                <div class="flex justify-between text-xxs text-gray-400 dark:text-gray-500 mb-1">
                                    <span>Beginner</span>
                                    <span>Intermediate</span>
                                    <span>Advanced</span>
                                    <span>Expert</span>
                                </div>
                                <div class="relative h-3 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                                    <?php 
                                        $intern_progress = calculate_level_progress($skill['current_level']);
                                        $supervisor_progress = $skill['supervisor_rating'] ? calculate_level_progress($skill['supervisor_rating']) : 0;
                                        $intern_color = get_level_color($skill['current_level']);
                                        $supervisor_color = $skill['supervisor_rating'] ? get_level_color($skill['supervisor_rating']) : '';
                                    ?>
                                    <!-- Supervisor assessment (base, full color, supervisor's color) -->
                                    <?php if ($skill['supervisor_rating']): ?>
                                    <div class="absolute h-3 bg-<?php echo $supervisor_color; ?>-500 dark:bg-<?php echo $supervisor_color; ?>-400 rounded-full" style="width: <?php echo $supervisor_progress; ?>%"></div>
                                    <?php endif; ?>
                                    <!-- Intern assessment (overlay, lighter color, intern's color) -->
                                    <div class="absolute h-3 bg-<?php echo $intern_color; ?>-300 dark:bg-<?php echo $intern_color; ?>-700 rounded-full" style="width: <?php echo $intern_progress; ?>%"></div>
                                </div>
                            </div>
                            <?php if ($skill['supervisor_comments']): ?>
                                <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-md border border-gray-200 dark:border-gray-600">
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 transition-colors duration-200">Your Feedback:</p>
                                    <p class="text-gray-600 dark:text-gray-400 italic">"<?php echo htmlspecialchars($skill['supervisor_comments']); ?>"</p>
                                </div>
                            <?php endif; ?>
                            <?php if ($skill['supervisor_rating'] && $edit_skill_id !== $skill['skill_id']): ?>
                                <!-- Show summary and Edit button -->
                                <div class="flex justify-end">
                                    <?php if (!$is_admin_view_mode): ?>
                                    <form action="evaluate_skill.php" method="POST">
                                        <input type="hidden" name="intern_id" value="<?php echo $selected_intern_id; ?>">
                                        <input type="hidden" name="edit_skill_id" value="<?php echo $skill['skill_id']; ?>">
                                        <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-6 rounded-lg shadow transition duration-200">Edit Evaluation</button>
                                    </form>
                                    <?php else: ?>
                                    <div class="bg-gray-400 dark:bg-gray-600 text-white font-bold py-2 px-6 rounded-lg shadow cursor-not-allowed opacity-50">
                                        Edit Evaluation (Read-Only)
                                    </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <!-- Show the form for new or editing -->
                                <?php if (!$is_admin_view_mode): ?>
                                <form action="evaluate_skill.php" method="POST" class="mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-md border border-gray-200 dark:border-gray-600">
                                    <input type="hidden" name="evaluate_skill" value="1">
                                    <input type="hidden" name="intern_id" value="<?php echo $selected_intern_id; ?>">
                                    <input type="hidden" name="skill_id" value="<?php echo $skill['skill_id']; ?>">
                                    <div class="mb-4">
                                        <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 transition-colors duration-200" for="rating_<?php echo $skill['skill_id']; ?>">Your Evaluation</label>
                                        <select name="rating" id="rating_<?php echo $skill['skill_id']; ?>" class="appearance-none rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white w-full py-2 px-3 text-gray-700 dark:text-gray-300 leading-tight focus:outline-none focus:ring-2 focus:ring-purple-500 transition-colors duration-200" required>
                                            <option value="">-- Select your evaluation --</option>
                                            <option value="Beginner" <?php echo ($skill['supervisor_rating'] === 'Beginner') ? 'selected' : ''; ?>>Beginner</option>
                                            <option value="Intermediate" <?php echo ($skill['supervisor_rating'] === 'Intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                                            <option value="Advanced" <?php echo ($skill['supervisor_rating'] === 'Advanced') ? 'selected' : ''; ?>>Advanced</option>
                                            <option value="Expert" <?php echo ($skill['supervisor_rating'] === 'Expert') ? 'selected' : ''; ?>>Expert</option>
                                        </select>
                                    </div>
                                    <div class="mb-4">
                                        <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 transition-colors duration-200" for="comments_<?php echo $skill['skill_id']; ?>">Feedback Comments</label>
                                        <textarea name="comments" id="comments_<?php echo $skill['skill_id']; ?>" rows="3" class="appearance-none rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white w-full py-2 px-3 text-gray-700 dark:text-gray-300 leading-tight focus:outline-none focus:ring-2 focus:ring-purple-500 transition-colors duration-200" placeholder="Provide constructive feedback..."><?php echo htmlspecialchars($skill['supervisor_comments'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="flex justify-end">
                                        <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-6 rounded-lg shadow transition duration-200">
                                            <?php echo $skill['supervisor_rating'] ? 'Update Evaluation' : 'Submit Evaluation'; ?>
                                        </button>
                                    </div>
                                </form>
                                <?php else: ?>
                                <div class="mt-4 p-4 bg-gray-100 dark:bg-gray-800 rounded-md border border-gray-300 dark:border-gray-600">
                                    <div class="mb-4">
                                        <label class="block text-gray-500 dark:text-gray-400 text-sm font-bold mb-2">Your Evaluation (Read-Only)</label>
                                        <div class="appearance-none rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white w-full py-2 px-3 text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800">
                                            <?php echo $skill['supervisor_rating'] ? htmlspecialchars($skill['supervisor_rating']) : 'Not evaluated yet'; ?>
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label class="block text-gray-500 dark:text-gray-400 text-sm font-bold mb-2">Feedback Comments (Read-Only)</label>
                                        <div class="appearance-none rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white w-full py-2 px-3 text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 min-h-[60px]">
                                            <?php echo $skill['supervisor_comments'] ? htmlspecialchars($skill['supervisor_comments']) : 'No comments provided'; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Other Skills (legacy/old custom skills) -->
        <?php if (!empty($other_skills)): ?>
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 mb-10 transition-colors duration-200">
            <h2 class="text-xl font-semibold mb-6 text-gray-700 dark:text-gray-300 transition-colors duration-200">Other Skills (Legacy)</h2>
            <div class="mb-4 p-3 bg-yellow-100 dark:bg-yellow-700 rounded-md border border-yellow-300 dark:border-yellow-600 text-yellow-800 dark:text-yellow-100">
                <strong>Note:</strong> These skills were added before the system update and may need to be re-categorized as 'Technical' or 'Soft'.
            </div>
            <div class="space-y-6">
                <?php foreach ($other_skills as $skill): ?>
                    <div class="border rounded-2xl p-6 shadow">
                        <div class="flex flex-col md:flex-row md:justify-between md:items-start mb-4">
                            <div>
                                <h3 class="font-semibold text-lg text-gray-800 dark:text-white transition-colors duration-200"><?php echo htmlspecialchars($skill['skill_name']); ?></h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 transition-colors duration-200"><?php echo htmlspecialchars($skill['skill_description']); ?></p>
                            </div>
                            <div class="mt-2 md:mt-0 flex flex-col md:items-end">
                                <div class="mb-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 transition-colors duration-200">Intern Assessment:</span>
                                    <?php 
                                        $level_color = get_level_color($skill['current_level']);
                                        $bg_color = "bg-{$level_color}-100";
                                        $text_color = "text-{$level_color}-800";
                                    ?>
                                    <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full bg-<?php echo $level_color; ?>-300 dark:bg-<?php echo $level_color; ?>-700 text-<?php echo $level_color; ?>-800 dark:text-<?php echo $level_color; ?>-200">
                                        <?php echo $skill['current_level']; ?>
                                    </span>
                                </div>
                                <?php if ($skill['supervisor_rating']): ?>
                                <div>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 transition-colors duration-200">Your Evaluation:</span>
                                    <?php 
                                        $sup_color = get_level_color($skill['supervisor_rating']);
                                        $sup_bg_color = "bg-{$sup_color}-100";
                                        $sup_text_color = "text-{$sup_color}-800";
                                    ?>
                                    <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full bg-<?php echo $sup_color; ?>-500 dark:bg-<?php echo $sup_color; ?>-400 text-<?php echo $sup_color; ?>-100 dark:text-<?php echo $sup_color; ?>-900">
                                        <?php echo $skill['supervisor_rating']; ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                                <br>
                            </div>
                            <!-- Static level scale -->
                            <div class="flex justify-between text-xxs text-gray-400 dark:text-gray-500 mb-1">
                                <span>Beginner</span>
                                <span>Intermediate</span>
                                <span>Advanced</span>
                                <span>Expert</span>
                            </div>
                            <div class="relative h-3 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                                <?php 
                                    $intern_progress = calculate_level_progress($skill['current_level']);
                                    $supervisor_progress = $skill['supervisor_rating'] ? calculate_level_progress($skill['supervisor_rating']) : 0;
                                    $intern_color = get_level_color($skill['current_level']);
                                    $supervisor_color = $skill['supervisor_rating'] ? get_level_color($skill['supervisor_rating']) : '';
                                ?>
                                <!-- Supervisor assessment (base, full color, supervisor's color) -->
                                <?php if ($skill['supervisor_rating']): ?>
                                <div class="absolute h-3 bg-<?php echo $supervisor_color; ?>-500 dark:bg-<?php echo $supervisor_color; ?>-400 rounded-full" style="width: <?php echo $supervisor_progress; ?>%"></div>
                                <?php endif; ?>
                                <!-- Intern assessment (overlay, lighter color, intern's color) -->
                                <div class="absolute h-3 bg-<?php echo $intern_color; ?>-300 dark:bg-<?php echo $intern_color; ?>-700 rounded-full" style="width: <?php echo $intern_progress; ?>%"></div>
                            </div>
                        </div>
                        <?php if ($skill['supervisor_comments']): ?>
                            <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-md border border-gray-200 dark:border-gray-600">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 transition-colors duration-200">Your Feedback:</p>
                                <p class="text-gray-600 dark:text-gray-400 italic">"<?php echo htmlspecialchars($skill['supervisor_comments'] ?? ''); ?>"</p>
                            </div>
                        <?php endif; ?>
                        <?php if ($skill['supervisor_rating'] && $edit_skill_id !== $skill['skill_id']): ?>
                            <!-- Show summary and Edit button -->
                            <div class="flex justify-end">
                                <?php if (!$is_admin_view_mode): ?>
                                <form action="evaluate_skill.php" method="POST">
                                    <input type="hidden" name="intern_id" value="<?php echo $selected_intern_id; ?>">
                                    <input type="hidden" name="edit_skill_id" value="<?php echo $skill['skill_id']; ?>">
                                    <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-6 rounded-lg shadow transition duration-200">Edit Evaluation</button>
                                </form>
                                <?php else: ?>
                                <div class="bg-gray-400 dark:bg-gray-600 text-white font-bold py-2 px-6 rounded-lg shadow cursor-not-allowed opacity-50">
                                    Edit Evaluation (Read-Only)
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <!-- Show the form for new or editing -->
                            <?php if (!$is_admin_view_mode): ?>
                            <form action="evaluate_skill.php" method="POST" class="mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-md border border-gray-200 dark:border-gray-600">
                                <input type="hidden" name="evaluate_skill" value="1">
                                <input type="hidden" name="intern_id" value="<?php echo $selected_intern_id; ?>">
                                <input type="hidden" name="skill_id" value="<?php echo $skill['skill_id']; ?>">
                                <div class="mb-4">
                                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 transition-colors duration-200" for="rating_<?php echo $skill['skill_id']; ?>">Your Evaluation</label>
                                    <select name="rating" id="rating_<?php echo $skill['skill_id']; ?>" class="appearance-none rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white w-full py-2 px-3 text-gray-700 dark:text-gray-300 leading-tight focus:outline-none focus:ring-2 focus:ring-purple-500 transition-colors duration-200" required>
                                        <option value="">-- Select your evaluation --</option>
                                        <option value="Beginner" <?php echo ($skill['supervisor_rating'] === 'Beginner') ? 'selected' : ''; ?>>Beginner</option>
                                        <option value="Intermediate" <?php echo ($skill['supervisor_rating'] === 'Intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                                        <option value="Advanced" <?php echo ($skill['supervisor_rating'] === 'Advanced') ? 'selected' : ''; ?>>Advanced</option>
                                        <option value="Expert" <?php echo ($skill['supervisor_rating'] === 'Expert') ? 'selected' : ''; ?>>Expert</option>
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 transition-colors duration-200" for="comments_<?php echo $skill['skill_id']; ?>">Feedback Comments</label>
                                    <textarea name="comments" id="comments_<?php echo $skill['skill_id']; ?>" rows="3" class="appearance-none rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white w-full py-2 px-3 text-gray-700 dark:text-gray-300 leading-tight focus:outline-none focus:ring-2 focus:ring-purple-500 transition-colors duration-200" placeholder="Provide constructive feedback..."><?php echo htmlspecialchars($skill['supervisor_comments'] ?? ''); ?></textarea>
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-6 rounded-lg shadow transition duration-200">
                                        <?php echo $skill['supervisor_rating'] ? 'Update Evaluation' : 'Submit Evaluation'; ?>
                                    </button>
                                </div>
                            </form>
                            <?php else: ?>
                            <div class="mt-4 p-4 bg-gray-100 dark:bg-gray-800 rounded-md border border-gray-300 dark:border-gray-600">
                                <div class="mb-4">
                                    <label class="block text-gray-500 dark:text-gray-400 text-sm font-bold mb-2">Your Evaluation (Read-Only)</label>
                                    <div class="appearance-none rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white w-full py-2 px-3 text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800">
                                        <?php echo $skill['supervisor_rating'] ? htmlspecialchars($skill['supervisor_rating']) : 'Not evaluated yet'; ?>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-gray-500 dark:text-gray-400 text-sm font-bold mb-2">Feedback Comments (Read-Only)</label>
                                    <div class="appearance-none rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white w-full py-2 px-3 text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 min-h-[60px]">
                                        <?php echo $skill['supervisor_comments'] ? htmlspecialchars($skill['supervisor_comments']) : 'No comments provided'; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php else: ?>
        <!-- No intern selected message -->
        <div class="bg-blue-50 dark:bg-gray-800 border border-blue-200 dark:border-gray-700 p-8 rounded-2xl shadow-xl">
            <p class="text-lg text-blue-800 dark:text-blue-200">Please select an intern to view and evaluate their skills.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once "../includes/footer.php";
?>
