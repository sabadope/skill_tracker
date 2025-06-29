<?php
// Skills list page for interns to view and update their skills
require_once "../config/database.php";
require_once "../includes/auth.php";
require_once "../includes/functions.php";

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please log in to view your skills.";
    header("Location: ../index.php");
    exit;
}

// Check if user is an intern (or admin in view mode)
require_role_with_admin_view('intern');

// Check if admin is in view mode
$is_admin_view_mode = is_admin_in_view_mode();
$admin_view_mode = get_admin_view_mode();
$selected_user_id = get_admin_selected_user_id();

// Clear any error messages if this is just a page view
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    unset($_SESSION['error']);
}

// Get database connection
$database = new Database();
$conn = $database->getConnection();

// Get user data
$user_id = $_SESSION['user_id'];
$user = get_user_by_id($conn, $user_id);

// Get all skills (for adding new skills)
$all_skills = get_all_skills($conn);

// Get user's current skills
$user_skills = get_user_skills($conn, $user_id);

// Organize skills by category
$technical_skills = array_filter($user_skills, function($skill) {
    return $skill['skill_category'] === 'technical';
});

$soft_skills = array_filter($user_skills, function($skill) {
    return $skill['skill_category'] === 'soft';
});

$other_skills = array_filter($user_skills, function($skill) {
    return $skill['skill_category'] === 'other';
});

// Group all skills by category for the dropdown (no filtering)
$available_technical = array_filter($all_skills, function($skill) {
    return $skill['category'] === 'technical';
});

$available_soft = array_filter($all_skills, function($skill) {
    return $skill['category'] === 'soft';
});

$available_other = array_filter($all_skills, function($skill) {
    return $skill['category'] === 'other';
});

// Include header
require_once "../includes/header.php";
?>

<div class="min-h-screen bg-gray-100 dark:bg-gray-900 py-8 transition-colors duration-300">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php include_once "../includes/admin_view_banner.php"; ?>

        <div class="flex flex-col sm:flex-row justify-between items-center mb-10 bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 transition-colors duration-300">
            <h1 class="text-4xl font-extrabold text-gray-900 dark:text-white mb-4 sm:mb-0 transition-colors duration-300">
                <?php if ($is_admin_view_mode): ?>
                    My Skills Dashboard (Read-Only View)
                <?php else: ?>
                    My Skills Dashboard
                <?php endif; ?>
            </h1>
            <?php if (!$is_admin_view_mode): ?>
            <button id="addSkillBtn" class="bg-gradient-to-r from-blue-500 to-blue-700 hover:from-blue-600 hover:to-blue-800 text-white font-bold py-3 px-8 rounded-full shadow-lg transition duration-300 transform hover:scale-105">
                <i class="fas fa-plus-circle mr-2"></i> Add New Skill
            </button>
            <?php else: ?>
            <div class="bg-gray-400 dark:bg-gray-600 text-white font-bold py-3 px-8 rounded-full shadow-lg cursor-not-allowed opacity-50">
                <i class="fas fa-plus-circle mr-2"></i> Add New Skill (Read-Only)
            </div>
            <?php endif; ?>
        </div>

        <!-- Add Skill Modal -->
        <div id="addSkillModal" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center hidden z-50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full p-8 relative transform transition-all sm:my-8 sm:align-middle sm:max-w-md md:max-w-lg lg:max-w-xl duration-300 scale-95 opacity-0" id="modalContent">
                <button id="closeModalBtn" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:text-gray-400 dark:hover:text-gray-200 transition-colors duration-200">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-6 text-center transition-colors duration-300">Add New Skill</h2>
                <form action="update_skill.php" method="POST" id="addSkillForm">
                    <div class="mb-6 p-4 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 transition-colors duration-300">
                        <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-3 transition-colors duration-300">Choose Skill Type</label>
                        <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-6">
                            <label class="inline-flex items-center cursor-pointer p-3 rounded-lg border border-transparent hover:border-blue-300 dark:hover:border-blue-600 transition duration-200">
                                <input type="radio" name="skill_type" value="existing" class="form-radio text-blue-600 h-5 w-5" checked>
                                <span class="ml-3 text-gray-800 dark:text-gray-200 text-base font-medium transition-colors duration-300">Select Existing Skill</span>
                            </label>
                            <label class="inline-flex items-center cursor-pointer p-3 rounded-lg border border-transparent hover:border-blue-300 dark:hover:border-blue-600 transition duration-200">
                                <input type="radio" name="skill_type" value="custom" class="form-radio text-blue-600 h-5 w-5">
                                <span class="ml-3 text-gray-800 dark:text-gray-200 text-base font-medium transition-colors duration-300">Add Custom Skill</span>
                            </label>
                        </div>
                    </div>
                    <div id="existingSkillSection" class="mb-6">
                        <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 transition-colors duration-300" for="skill_id">Select Skill</label>
                        <div class="relative">
                            <select name="skill_id" id="skill_id" class="block appearance-none w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 py-3 px-4 pr-8 rounded-lg leading-tight focus:outline-none focus:bg-white dark:focus:bg-gray-700 focus:border-blue-500 dark:focus:border-blue-400 transition duration-200" required>
                            <option value="">-- Select a skill --</option>
                            <?php if (!empty($available_technical)): ?>
                                <optgroup label="Technical Skills">
                                    <?php foreach ($available_technical as $skill): ?>
                                        <option value="<?php echo $skill['id']; ?>"><?php echo htmlspecialchars($skill['name']); ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                            <?php if (!empty($available_soft)): ?>
                                <optgroup label="Soft Skills">
                                    <?php foreach ($available_soft as $skill): ?>
                                        <option value="<?php echo $skill['id']; ?>"><?php echo htmlspecialchars($skill['name']); ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                            <?php if (!empty($available_other)): ?>
                                <optgroup label="Other Skills">
                                    <?php foreach ($available_other as $skill): ?>
                                        <option value="<?php echo $skill['id']; ?>"><?php echo htmlspecialchars($skill['name']); ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                        </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700 dark:text-gray-300">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">If you already have this skill, please update its level from your My Skills list below.</p>
                    </div>
                    <div id="customSkillSection" class="mb-6 hidden bg-gray-50 dark:bg-gray-700 p-4 rounded-lg border border-gray-200 dark:border-gray-600 transition-colors duration-300">
                        <div class="mb-4">
                            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 transition-colors duration-300" for="custom_skill_name">Skill Name</label>
                            <input type="text" name="custom_skill_name" id="custom_skill_name" class="shadow-sm appearance-none border border-gray-300 dark:border-gray-600 rounded-lg w-full py-3 px-4 text-gray-700 dark:text-gray-300 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 transition duration-200" placeholder="e.g., Data Analysis with Python">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 transition-colors duration-300" for="custom_skill_category">Category</label>
                            <div class="relative">
                                <select name="custom_skill_category" id="custom_skill_category" class="block appearance-none w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 py-3 px-4 pr-8 rounded-lg leading-tight focus:outline-none focus:bg-white dark:focus:bg-gray-700 focus:border-blue-500 dark:focus:border-blue-400 transition duration-200">
                                <option value="technical">Technical</option>
                                <option value="soft">Soft Skills</option>
                            </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700 dark:text-gray-300">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                                </div>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 transition-colors duration-300" for="custom_skill_description">Description</label>
                            <textarea name="custom_skill_description" id="custom_skill_description" rows="4" class="shadow-sm appearance-none border border-gray-300 dark:border-gray-600 rounded-lg w-full py-3 px-4 text-gray-700 dark:text-gray-300 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 transition duration-200" placeholder="Provide a brief description of this skill"></textarea>
                        </div>
                    </div>
                    <div class="mb-6">
                        <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 transition-colors duration-300" for="level">Your Current Level</label>
                        <div class="relative">
                            <select name="level" id="level" class="block appearance-none w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 py-3 px-4 pr-8 rounded-lg leading-tight focus:outline-none focus:bg-white dark:focus:bg-gray-700 focus:border-blue-500 dark:focus:border-blue-400 transition duration-200" required>
                            <option value="">-- Select your current level --</option>
                            <option value="Beginner">Beginner</option>
                            <option value="Intermediate">Intermediate</option>
                            <option value="Advanced">Advanced</option>
                            <option value="Expert">Expert</option>
                        </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700 dark:text-gray-300">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-bold py-3 px-8 rounded-lg shadow-md transition duration-300 transform hover:scale-105">
                            <i class="fas fa-save mr-2"></i> Add Skill
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Skill Sections -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-10">
            <!-- Technical Skills -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 border border-gray-200 dark:border-gray-700 transition-colors duration-300">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-6 pb-3 border-b-2 border-blue-500 dark:border-blue-400 flex items-center transition-colors duration-300">
                    <i class="fas fa-laptop-code text-blue-600 dark:text-blue-400 mr-3"></i> Technical Skills
                </h2>
                <?php if (empty($technical_skills)): ?>
                    <div class="text-center py-10 text-gray-500 dark:text-gray-400 transition-colors duration-300">
                        <i class="fas fa-frown text-6xl mb-4"></i>
                        <p class="text-lg">No technical skills added yet. Time to learn something new!</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-6">
                        <?php foreach ($technical_skills as $skill): ?>
                            <div class="border border-gray-200 dark:border-gray-600 rounded-xl p-6 shadow-md hover:shadow-lg transition duration-200 <?php echo $skill['supervisor_rating'] ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-600' : 'bg-white dark:bg-gray-800'; ?>">
                                <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-3">
                                    <div>
                                        <h3 class="font-bold text-xl text-gray-900 dark:text-white mb-1 transition-colors duration-300"><?php echo htmlspecialchars($skill['skill_name']); ?></h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 transition-colors duration-300"><?php echo htmlspecialchars($skill['skill_description']); ?></p>
                                    </div>
                                    <div class="mt-3 md:mt-0">
                                        <?php 
                                            $level_color = get_level_color($skill['current_level']);
                                            $bg_color = "bg-{$level_color}-100 dark:bg-{$level_color}-900";
                                            $text_color = "text-{$level_color}-800 dark:text-{$level_color}-200";
                                        ?>
                                        <span class="px-4 py-1.5 inline-flex text-sm font-semibold rounded-full <?php echo $bg_color . ' ' . $text_color; ?> transition-colors duration-300">
                                            <i class="fas fa-user-tag mr-2"></i> Intern Assessment: <?php echo $skill['current_level']; ?>
                                        </span>
                                        <?php if ($skill['supervisor_rating']): ?>
                                        <?php 
                                            $sup_color = get_level_color($skill['supervisor_rating']);
                                            $sup_bg_color = "bg-{$sup_color}-100 dark:bg-{$sup_color}-900";
                                            $sup_text_color = "text-{$sup_color}-800 dark:text-{$sup_color}-200";
                                        ?>
                                        <span class="ml-2 px-4 py-1.5 inline-flex text-sm font-semibold rounded-full <?php echo $sup_bg_color . ' ' . $sup_text_color; ?> transition-colors duration-300">
                                            <i class="fas fa-star mr-2"></i> Supervisor Assessment: <?php echo $skill['supervisor_rating']; ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1 transition-colors duration-300">
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
                                <?php if ($skill['supervisor_rating']): ?>
                                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 p-4 rounded-lg transition-colors duration-300">
                                    <div class="flex items-center mb-2">
                                        <h4 class="font-bold text-gray-800 dark:text-white text-base flex items-center transition-colors duration-300">
                                            <i class="fas fa-clipboard-check text-green-600 dark:text-green-400 mr-2"></i> Supervisor Assessment:
                                        </h4>
                                    </div>
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <?php 
                                                $sup_color = get_level_color($skill['supervisor_rating']);
                                                $sup_bg_color = "bg-{$sup_color}-100 dark:bg-{$sup_color}-900";
                                                $sup_text_color = "text-{$sup_color}-800 dark:text-{$sup_color}-200";
                                            ?>
                                            <span class="px-3 py-1 text-sm font-semibold rounded-full <?php echo $sup_bg_color . ' ' . $sup_text_color; ?> transition-colors duration-300">
                                                <i class="fas fa-star mr-2"></i> <?php echo $skill['supervisor_rating']; ?>
                                            </span>
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-2 sm:mt-0 transition-colors duration-300">
                                            <?php if ($skill['supervisor_first_name']): ?>
                                                By: <?php echo htmlspecialchars($skill['supervisor_first_name'] . ' ' . $skill['supervisor_last_name']); ?> on <?php echo date('M d, Y', strtotime($skill['last_updated'])); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if ($skill['supervisor_comments']): ?>
                                        <div class="mt-3 text-sm">
                                            <p class="font-medium text-gray-700 dark:text-gray-300 mb-1 transition-colors duration-300">Feedback:</p>
                                            <p class="text-gray-600 dark:text-gray-400 italic transition-colors duration-300">"<?php echo htmlspecialchars($skill['supervisor_comments']); ?>"</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600 bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg text-sm text-yellow-800 dark:text-yellow-200 flex items-center transition-colors duration-300">
                                    <i class="fas fa-clock mr-2"></i> Awaiting supervisor assessment
                                </div>
                                <?php endif; ?>
                                <div class="mt-4 flex flex-col sm:flex-row sm:justify-between sm:items-center pt-4 border-t border-gray-200 dark:border-gray-600">
                                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-3 sm:mb-0 transition-colors duration-300">
                                        Last updated by you: <?php echo date('M d, Y', strtotime($skill['last_updated'])); ?>
                                    </div>
                                    <?php if ($skill['supervisor_rating']): ?>
                                        <div class="text-sm text-red-500 dark:text-red-400 font-semibold">
                                            This skill has been assessed by your supervisor and can no longer be edited.
                                        </div>
                                    <?php else: ?>
                                    <form action="update_skill.php" method="POST" class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3 w-full sm:w-auto">
                                        <input type="hidden" name="skill_id" value="<?php echo $skill['skill_id']; ?>">
                                        <select name="level" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 transition duration-200 w-full sm:w-auto">
                                            <option value="Beginner" <?php echo ($skill['current_level'] === 'Beginner') ? 'selected' : ''; ?>>Beginner</option>
                                            <option value="Intermediate" <?php echo ($skill['current_level'] === 'Intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                                            <option value="Advanced" <?php echo ($skill['current_level'] === 'Advanced') ? 'selected' : ''; ?>>Advanced</option>
                                            <option value="Expert" <?php echo ($skill['current_level'] === 'Expert') ? 'selected' : ''; ?>>Expert</option>
                                        </select>
                                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white text-sm py-2 px-4 rounded-lg shadow-md transition duration-200 transform hover:scale-105">
                                            <i class="fas fa-sync-alt mr-1"></i> Update Level
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Soft Skills -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 border border-gray-200 dark:border-gray-700 transition-colors duration-300">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-6 pb-3 border-b-2 border-purple-500 dark:border-purple-400 flex items-center transition-colors duration-300">
                    <i class="fas fa-users text-purple-600 dark:text-purple-400 mr-3"></i> Soft Skills
                </h2>
                <?php if (empty($soft_skills)): ?>
                    <div class="text-center py-10 text-gray-500 dark:text-gray-400 transition-colors duration-300">
                        <i class="fas fa-frown text-6xl mb-4"></i>
                        <p class="text-lg">No soft skills added yet. Time to develop those interpersonal abilities!</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-6">
                        <?php foreach ($soft_skills as $skill): ?>
                            <div class="border border-gray-200 dark:border-gray-600 rounded-xl p-6 shadow-md hover:shadow-lg transition duration-200 <?php echo $skill['supervisor_rating'] ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-600' : 'bg-white dark:bg-gray-800'; ?>">
                                <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-3">
                                    <div>
                                        <h3 class="font-bold text-xl text-gray-900 dark:text-white mb-1 transition-colors duration-300"><?php echo htmlspecialchars($skill['skill_name']); ?></h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 transition-colors duration-300"><?php echo htmlspecialchars($skill['skill_description']); ?></p>
                                    </div>
                                    <div class="mt-3 md:mt-0">
                                        <?php 
                                            $level_color = get_level_color($skill['current_level']);
                                            $bg_color = "bg-{$level_color}-100 dark:bg-{$level_color}-900";
                                            $text_color = "text-{$level_color}-800 dark:text-{$level_color}-200";
                                        ?>
                                        <span class="px-4 py-1.5 inline-flex text-sm font-semibold rounded-full <?php echo $bg_color . ' ' . $text_color; ?> transition-colors duration-300">
                                            <i class="fas fa-user-tag mr-2"></i> Intern Assessment: <?php echo $skill['current_level']; ?>
                                        </span>
                                        <?php if ($skill['supervisor_rating']): ?>
                                        <?php 
                                            $sup_color = get_level_color($skill['supervisor_rating']);
                                            $sup_bg_color = "bg-{$sup_color}-100 dark:bg-{$sup_color}-900";
                                            $sup_text_color = "text-{$sup_color}-800 dark:text-{$sup_color}-200";
                                        ?>
                                        <span class="ml-2 px-4 py-1.5 inline-flex text-sm font-semibold rounded-full <?php echo $sup_bg_color . ' ' . $sup_text_color; ?> transition-colors duration-300">
                                            <i class="fas fa-star mr-2"></i> Supervisor Assessment: <?php echo $skill['supervisor_rating']; ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1 transition-colors duration-300">
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
                                <?php if ($skill['supervisor_rating']): ?>
                                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 p-4 rounded-lg transition-colors duration-300">
                                    <div class="flex items-center mb-2">
                                        <h4 class="font-bold text-gray-800 dark:text-white text-base flex items-center transition-colors duration-300">
                                            <i class="fas fa-clipboard-check text-green-600 dark:text-green-400 mr-2"></i> Supervisor Assessment:
                                        </h4>
                                    </div>
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <?php 
                                                $sup_color = get_level_color($skill['supervisor_rating']);
                                                $sup_bg_color = "bg-{$sup_color}-100 dark:bg-{$sup_color}-900";
                                                $sup_text_color = "text-{$sup_color}-800 dark:text-{$sup_color}-200";
                                            ?>
                                            <span class="px-3 py-1 text-sm font-semibold rounded-full <?php echo $sup_bg_color . ' ' . $sup_text_color; ?> transition-colors duration-300">
                                                <i class="fas fa-star mr-2"></i> <?php echo $skill['supervisor_rating']; ?>
                                            </span>
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-2 sm:mt-0 transition-colors duration-300">
                                            <?php if ($skill['supervisor_first_name']): ?>
                                                By: <?php echo htmlspecialchars($skill['supervisor_first_name'] . ' ' . $skill['supervisor_last_name']); ?> on <?php echo date('M d, Y', strtotime($skill['last_updated'])); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if ($skill['supervisor_comments']): ?>
                                        <div class="mt-3 text-sm">
                                            <p class="font-medium text-gray-700 dark:text-gray-300 mb-1 transition-colors duration-300">Feedback:</p>
                                            <p class="text-gray-600 dark:text-gray-400 italic transition-colors duration-300">"<?php echo htmlspecialchars($skill['supervisor_comments']); ?>"</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600 bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg text-sm text-yellow-800 dark:text-yellow-200 flex items-center transition-colors duration-300">
                                    <i class="fas fa-clock mr-2"></i> Awaiting supervisor assessment
                                </div>
                                <?php endif; ?>
                                <div class="mt-4 flex flex-col sm:flex-row sm:justify-between sm:items-center pt-4 border-t border-gray-200 dark:border-gray-600">
                                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-3 sm:mb-0 transition-colors duration-300">
                                        Last updated by you: <?php echo date('M d, Y', strtotime($skill['last_updated'])); ?>
                                    </div>
                                    <?php if ($skill['supervisor_rating']): ?>
                                        <div class="text-sm text-red-500 dark:text-red-400 font-semibold">
                                            This skill has been assessed by your supervisor and can no longer be edited.
                                        </div>
                                    <?php else: ?>
                                    <form action="update_skill.php" method="POST" class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3 w-full sm:w-auto">
                                        <input type="hidden" name="skill_id" value="<?php echo $skill['skill_id']; ?>">
                                        <select name="level" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 transition duration-200 w-full sm:w-auto">
                                            <option value="Beginner" <?php echo ($skill['current_level'] === 'Beginner') ? 'selected' : ''; ?>>Beginner</option>
                                            <option value="Intermediate" <?php echo ($skill['current_level'] === 'Intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                                            <option value="Advanced" <?php echo ($skill['current_level'] === 'Advanced') ? 'selected' : ''; ?>>Advanced</option>
                                            <option value="Expert" <?php echo ($skill['current_level'] === 'Expert') ? 'selected' : ''; ?>>Expert</option>
                                        </select>
                                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white text-sm py-2 px-4 rounded-lg shadow-md transition duration-200 transform hover:scale-105">
                                            <i class="fas fa-sync-alt mr-1"></i> Update Level
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Other Skills (if any) -->
            <?php if (!empty($other_skills)): ?>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 border border-gray-200 dark:border-gray-700 transition-colors duration-300">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-6 pb-3 border-b-2 border-gray-400 dark:border-gray-500 flex items-center transition-colors duration-300">
                    <i class="fas fa-lightbulb text-gray-600 dark:text-gray-400 mr-3"></i> Other Skills
                </h2>
                <div class="space-y-6">
                    <?php foreach ($other_skills as $skill): ?>
                        <div class="border border-gray-200 dark:border-gray-600 rounded-xl p-6 shadow-md hover:shadow-lg transition duration-200 <?php echo $skill['supervisor_rating'] ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-600' : 'bg-white dark:bg-gray-800'; ?>">
                            <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-3">
                                <div>
                                    <h3 class="font-bold text-xl text-gray-900 dark:text-white mb-1 transition-colors duration-300"><?php echo htmlspecialchars($skill['skill_name']); ?></h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 transition-colors duration-300"><?php echo htmlspecialchars($skill['skill_description']); ?></p>
                                </div>
                                <div class="mt-3 md:mt-0">
                                    <?php 
                                        $level_color = get_level_color($skill['current_level']);
                                        $bg_color = "bg-{$level_color}-100 dark:bg-{$level_color}-900";
                                        $text_color = "text-{$level_color}-800 dark:text-{$level_color}-200";
                                    ?>
                                    <span class="px-4 py-1.5 inline-flex text-sm font-semibold rounded-full <?php echo $bg_color . ' ' . $text_color; ?> transition-colors duration-300">
                                        <i class="fas fa-user-tag mr-2"></i> Intern Assessment: <?php echo $skill['current_level']; ?>
                                    </span>
                                    <?php if ($skill['supervisor_rating']): ?>
                                    <?php 
                                        $sup_color = get_level_color($skill['supervisor_rating']);
                                        $sup_bg_color = "bg-{$sup_color}-100 dark:bg-{$sup_color}-900";
                                        $sup_text_color = "text-{$sup_color}-800 dark:text-{$sup_color}-200";
                                    ?>
                                    <span class="ml-2 px-4 py-1.5 inline-flex text-sm font-semibold rounded-full <?php echo $sup_bg_color . ' ' . $sup_text_color; ?> transition-colors duration-300">
                                        <i class="fas fa-star mr-2"></i> Supervisor Assessment: <?php echo $skill['supervisor_rating']; ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="mt-4">
                                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1 transition-colors duration-300">
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
                            <?php if ($skill['supervisor_rating']): ?>
                            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 p-4 rounded-lg transition-colors duration-300">
                                <div class="flex items-center mb-2">
                                    <h4 class="font-bold text-gray-800 dark:text-white text-base flex items-center transition-colors duration-300">
                                        <i class="fas fa-clipboard-check text-green-600 dark:text-green-400 mr-2"></i> Supervisor Assessment:
                                    </h4>
                                </div>
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <?php 
                                            $sup_color = get_level_color($skill['supervisor_rating']);
                                            $sup_bg_color = "bg-{$sup_color}-100 dark:bg-{$sup_color}-900";
                                            $sup_text_color = "text-{$sup_color}-800 dark:text-{$sup_color}-200";
                                        ?>
                                        <span class="px-3 py-1 text-sm font-semibold rounded-full <?php echo $sup_bg_color . ' ' . $sup_text_color; ?> transition-colors duration-300">
                                            <i class="fas fa-star mr-2"></i> <?php echo $skill['supervisor_rating']; ?>
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-2 sm:mt-0 transition-colors duration-300">
                                        <?php if ($skill['supervisor_first_name']): ?>
                                            By: <?php echo htmlspecialchars($skill['supervisor_first_name'] . ' ' . $skill['supervisor_last_name']); ?> on <?php echo date('M d, Y', strtotime($skill['last_updated'])); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($skill['supervisor_comments']): ?>
                                    <div class="mt-3 text-sm">
                                        <p class="font-medium text-gray-700 dark:text-gray-300 mb-1 transition-colors duration-300">Feedback:</p>
                                        <p class="text-gray-600 dark:text-gray-400 italic transition-colors duration-300">"<?php echo htmlspecialchars($skill['supervisor_comments']); ?>"</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600 bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg text-sm text-yellow-800 dark:text-yellow-200 flex items-center transition-colors duration-300">
                                <i class="fas fa-clock mr-2"></i> Awaiting supervisor assessment
                            </div>
                            <?php endif; ?>
                            <div class="mt-4 flex flex-col sm:flex-row sm:justify-between sm:items-center pt-4 border-t border-gray-200 dark:border-gray-600">
                                <div class="text-sm text-gray-500 dark:text-gray-400 mb-3 sm:mb-0 transition-colors duration-300">
                                    Last updated by you: <?php echo date('M d, Y', strtotime($skill['last_updated'])); ?>
                                </div>
                                <?php if ($skill['supervisor_rating']): ?>
                                    <div class="text-sm text-red-500 dark:text-red-400 font-semibold">
                                        This skill has been assessed by your supervisor and can no longer be edited.
                                    </div>
                                <?php else: ?>
                                <form action="update_skill.php" method="POST" class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3 w-full sm:w-auto">
                                    <input type="hidden" name="skill_id" value="<?php echo $skill['skill_id']; ?>">
                                    <select name="level" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 transition duration-200 w-full sm:w-auto">
                                        <option value="Beginner" <?php echo ($skill['current_level'] === 'Beginner') ? 'selected' : ''; ?>>Beginner</option>
                                        <option value="Intermediate" <?php echo ($skill['current_level'] === 'Intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                                        <option value="Advanced" <?php echo ($skill['current_level'] === 'Advanced') ? 'selected' : ''; ?>>Advanced</option>
                                        <option value="Expert" <?php echo ($skill['current_level'] === 'Expert') ? 'selected' : ''; ?>>Expert</option>
                                    </select>
                                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white text-sm py-2 px-4 rounded-lg shadow-md transition duration-200 transform hover:scale-105">
                                        <i class="fas fa-sync-alt mr-1"></i> Update Level
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Add Skill Modal
    const addSkillBtn = document.getElementById('addSkillBtn');
    const addSkillModal = document.getElementById('addSkillModal');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const skillTypeRadios = document.getElementsByName('skill_type');
    const existingSkillSection = document.getElementById('existingSkillSection');
    const customSkillSection = document.getElementById('customSkillSection');
    const addSkillForm = document.getElementById('addSkillForm');
    const modalContent = document.getElementById('modalContent');
    
    // Function to show modal with transition
    const showModal = () => {
        addSkillModal.classList.remove('hidden');
        setTimeout(() => {
            modalContent.classList.remove('opacity-0', 'scale-95');
            modalContent.classList.add('opacity-100', 'scale-100');
        }, 50);
    };

    // Function to hide modal with transition
    const hideModal = () => {
        modalContent.classList.remove('opacity-100', 'scale-100');
        modalContent.classList.add('opacity-0', 'scale-95');
        setTimeout(() => {
        addSkillModal.classList.add('hidden');
        }, 300); // Duration of the transition
    };
    
    addSkillBtn.addEventListener('click', showModal);
    closeModalBtn.addEventListener('click', hideModal);
    
    // Close modal when clicking outside
    addSkillModal.addEventListener('click', (e) => {
        if (e.target === addSkillModal) {
            hideModal();
        }
    });

    // Toggle between existing and custom skill sections
    skillTypeRadios.forEach(radio => {
        radio.addEventListener('change', (e) => {
            if (e.target.value === 'existing') {
                existingSkillSection.classList.remove('hidden');
                customSkillSection.classList.add('hidden');
                document.getElementById('skill_id').required = true;
                document.getElementById('custom_skill_name').required = false;
                document.getElementById('custom_skill_description').required = false;
            } else {
                existingSkillSection.classList.add('hidden');
                customSkillSection.classList.remove('hidden');
                document.getElementById('skill_id').required = false;
                document.getElementById('custom_skill_name').required = true;
                document.getElementById('custom_skill_description').required = true;
            }
        });
    });

    // Form submission handling
    addSkillForm.addEventListener('submit', (e) => {
        const skillType = document.querySelector('input[name="skill_type"]:checked').value;
        
        if (skillType === 'custom') {
            // Change form action for custom skill
            addSkillForm.action = 'add_custom_skill.php';
        } else {
            // Use default action for existing skill
            addSkillForm.action = 'update_skill.php';
        }
    });
</script>

<?php
require_once "../includes/footer.php";
?>
