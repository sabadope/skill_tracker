<?php
// Supervisor skill evaluation page
require_once "../config/database.php";
require_once "../includes/auth.php";
require_once "../includes/functions.php";

// Check if user is logged in and is a supervisor
require_role('supervisor');

// Get database connection
$database = new Database();
$conn = $database->getConnection();

// Get supervisor ID
$supervisor_id = $_SESSION['user_id'];

// Get all interns
$interns = get_users_by_role($conn, 'intern');

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check which form was submitted
    if (isset($_POST['evaluate_skill'])) {
        // Process skill evaluation
        $intern_id = isset($_POST['intern_id']) ? (int)$_POST['intern_id'] : 0;
        $skill_id = isset($_POST['skill_id']) ? (int)$_POST['skill_id'] : 0;
        $rating = isset($_POST['rating']) ? sanitize_input($_POST['rating']) : '';
        $comments = isset($_POST['comments']) ? sanitize_input($_POST['comments']) : '';
        
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
            if (evaluate_intern_skill($conn, $intern_id, $skill_id, $supervisor_id, $rating, $comments)) {
                $_SESSION['success'] = "Skill evaluation submitted successfully";
            } else {
                $_SESSION['error'] = "Failed to submit evaluation";
            }
        } else {
            $_SESSION['error'] = implode(", ", $errors);
        }
    } elseif (isset($_POST['assign_task'])) {
        // Process task assignment
        $intern_id = isset($_POST['intern_id']) ? (int)$_POST['intern_id'] : 0;
        $title = isset($_POST['title']) ? sanitize_input($_POST['title']) : '';
        $description = isset($_POST['description']) ? sanitize_input($_POST['description']) : '';
        $skill_id = isset($_POST['task_skill_id']) ? (int)$_POST['task_skill_id'] : null;
        $due_date = isset($_POST['due_date']) ? sanitize_input($_POST['due_date']) : '';
        
        // Validate form data
        $errors = [];
        if (empty($intern_id)) {
            $errors[] = "Intern is required";
        }
        
        if (empty($title)) {
            $errors[] = "Task title is required";
        }
        
        if (empty($due_date)) {
            $errors[] = "Due date is required";
        } elseif (strtotime($due_date) < strtotime(date('Y-m-d'))) {
            $errors[] = "Due date cannot be in the past";
        }
        
        // If skill_id is empty, set it to NULL for the database
        if (empty($skill_id)) {
            $skill_id = null;
        }
        
        // If no errors, create the task
        if (empty($errors)) {
            if (create_mentoring_task($conn, $intern_id, $supervisor_id, $title, $description, $skill_id, $due_date)) {
                $_SESSION['success'] = "Task assigned successfully";
            } else {
                $_SESSION['error'] = "Failed to assign task";
            }
        } else {
            $_SESSION['error'] = implode(", ", $errors);
        }
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

// Include header
require_once "../includes/header.php";
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold">Evaluate Intern Skills</h1>
    <?php if ($selected_intern): ?>
        <button id="assignTaskBtn" class="bg-purple-500 hover:bg-purple-600 text-white font-bold py-2 px-4 rounded transition duration-300">
            Assign Task
        </button>
    <?php endif; ?>
</div>

<!-- Intern Selection -->
<div class="bg-white rounded-lg shadow-md p-6 mb-8">
    <h2 class="text-xl font-semibold mb-4 text-gray-700">Select Intern</h2>
    
    <form method="GET" action="evaluate_skill.php">
        <div class="flex flex-col md:flex-row md:items-end space-y-4 md:space-y-0 md:space-x-4">
            <div class="flex-grow">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="intern_id">
                    Intern
                </label>
                <select name="intern_id" id="intern_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    <option value="">-- Select an intern --</option>
                    <?php foreach ($interns as $intern): ?>
                        <option value="<?php echo $intern['id']; ?>" <?php echo ($selected_intern_id == $intern['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($intern['first_name'] . ' ' . $intern['last_name'] . ' (' . $intern['department'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded transition duration-300">
                    View Skills
                </button>
            </div>
        </div>
    </form>
</div>

<?php if ($selected_intern): ?>
<!-- Intern Details -->
<div class="bg-white rounded-lg shadow-md p-6 mb-8">
    <div class="flex flex-col md:flex-row justify-between">
        <div>
            <h2 class="text-xl font-semibold mb-2 text-gray-700">
                <?php echo htmlspecialchars($selected_intern['first_name'] . ' ' . $selected_intern['last_name']); ?>
            </h2>
            <p class="text-gray-600">Department: <?php echo htmlspecialchars($selected_intern['department']); ?></p>
            <p class="text-gray-600">Email: <?php echo htmlspecialchars($selected_intern['email']); ?></p>
        </div>
    </div>
</div>

<!-- Assign Task Modal -->
<div id="assignTaskModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-800">Assign Task to <?php echo htmlspecialchars($selected_intern['first_name']); ?></h2>
            <button id="closeTaskModalBtn" class="text-gray-500 hover:text-gray-700">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form action="evaluate_skill.php" method="POST">
            <input type="hidden" name="assign_task" value="1">
            <input type="hidden" name="intern_id" value="<?php echo $selected_intern_id; ?>">
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="title">
                    Task Title
                </label>
                <input type="text" name="title" id="title" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="description">
                    Description
                </label>
                <textarea name="description" id="description" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="task_skill_id">
                    Related Skill (Optional)
                </label>
                <select name="task_skill_id" id="task_skill_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="">-- Select a skill (optional) --</option>
                    
                    <?php if (!empty($all_skills)): ?>
                        <optgroup label="Technical Skills">
                            <?php foreach ($all_skills as $skill): ?>
                                <?php if ($skill['category'] === 'technical'): ?>
                                    <option value="<?php echo $skill['id']; ?>"><?php echo htmlspecialchars($skill['name']); ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </optgroup>
                        
                        <optgroup label="Soft Skills">
                            <?php foreach ($all_skills as $skill): ?>
                                <?php if ($skill['category'] === 'soft'): ?>
                                    <option value="<?php echo $skill['id']; ?>"><?php echo htmlspecialchars($skill['name']); ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </optgroup>
                        
                        <optgroup label="Other Skills">
                            <?php foreach ($all_skills as $skill): ?>
                                <?php if ($skill['category'] === 'other'): ?>
                                    <option value="<?php echo $skill['id']; ?>"><?php echo htmlspecialchars($skill['name']); ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                </select>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="due_date">
                    Due Date
                </label>
                <input type="date" name="due_date" id="due_date" min="<?php echo date('Y-m-d'); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
            </div>
            
            <div class="flex justify-end">
                <button type="submit" class="bg-purple-500 hover:bg-purple-600 text-white font-bold py-2 px-4 rounded transition duration-300">
                    Assign Task
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Technical Skills -->
<div class="bg-white rounded-lg shadow-md p-6 mb-8">
    <h2 class="text-xl font-semibold mb-4 text-gray-700">Technical Skills</h2>
    
    <?php if (empty($technical_skills)): ?>
        <p class="text-gray-600">No technical skills added yet.</p>
    <?php else: ?>
        <div class="space-y-6">
            <?php foreach ($technical_skills as $skill): ?>
                <div class="border rounded-lg p-4">
                    <div class="flex flex-col md:flex-row md:justify-between md:items-start mb-4">
                        <div>
                            <h3 class="font-semibold text-lg text-gray-800"><?php echo htmlspecialchars($skill['skill_name']); ?></h3>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($skill['skill_description']); ?></p>
                        </div>
                        <div class="mt-2 md:mt-0 flex flex-col md:items-end">
                            <div class="mb-2">
                                <span class="text-sm font-medium text-gray-700">Self Assessment:</span>
                                <?php 
                                    $level_color = get_level_color($skill['current_level']);
                                    $bg_color = "bg-{$level_color}-100";
                                    $text_color = "text-{$level_color}-800";
                                ?>
                                <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full <?php echo $bg_color . ' ' . $text_color; ?>">
                                    <?php echo $skill['current_level']; ?>
                                </span>
                            </div>
                            
                            <?php if ($skill['supervisor_rating']): ?>
                            <div>
                                <span class="text-sm font-medium text-gray-700">Your Evaluation:</span>
                                <?php 
                                    $sup_color = get_level_color($skill['supervisor_rating']);
                                    $sup_bg_color = "bg-{$sup_color}-100";
                                    $sup_text_color = "text-{$sup_color}-800";
                                ?>
                                <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full <?php echo $sup_bg_color . ' ' . $sup_text_color; ?>">
                                    <?php echo $skill['supervisor_rating']; ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="flex justify-between text-xs text-gray-600 mb-1">
                            <span>Beginner</span>
                            <span>Intermediate</span>
                            <span>Advanced</span>
                            <span>Expert</span>
                        </div>
                        <div class="relative h-2 bg-gray-200 rounded-full">
                            <?php 
                                $intern_progress = calculate_level_progress($skill['current_level']);
                                $color = get_level_color($skill['current_level']);
                            ?>
                            <div class="absolute h-2 bg-<?php echo $color; ?>-500 rounded-full" style="width: <?php echo $intern_progress; ?>%"></div>
                            
                            <?php if ($skill['supervisor_rating']): ?>
                                <?php 
                                    $supervisor_progress = calculate_level_progress($skill['supervisor_rating']);
                                    $sup_color = get_level_color($skill['supervisor_rating']);
                                ?>
                                <div class="absolute h-2 bg-<?php echo $sup_color; ?>-500 rounded-full opacity-50" style="width: <?php echo $supervisor_progress; ?>%"></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($skill['supervisor_comments']): ?>
                        <div class="mb-4 p-3 bg-gray-50 rounded-md">
                            <p class="text-sm font-medium text-gray-700">Your Feedback:</p>
                            <p class="text-gray-600 italic">"<?php echo htmlspecialchars($skill['supervisor_comments']); ?>"</p>
                        </div>
                    <?php endif; ?>
                    
                    <form action="evaluate_skill.php" method="POST" class="mt-4 p-4 bg-gray-50 rounded-md">
                        <input type="hidden" name="evaluate_skill" value="1">
                        <input type="hidden" name="intern_id" value="<?php echo $selected_intern_id; ?>">
                        <input type="hidden" name="skill_id" value="<?php echo $skill['skill_id']; ?>">
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="rating_<?php echo $skill['skill_id']; ?>">
                                Your Evaluation
                            </label>
                            <select name="rating" id="rating_<?php echo $skill['skill_id']; ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                <option value="">-- Select your evaluation --</option>
                                <option value="Beginner" <?php echo ($skill['supervisor_rating'] === 'Beginner') ? 'selected' : ''; ?>>Beginner</option>
                                <option value="Intermediate" <?php echo ($skill['supervisor_rating'] === 'Intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                                <option value="Advanced" <?php echo ($skill['supervisor_rating'] === 'Advanced') ? 'selected' : ''; ?>>Advanced</option>
                                <option value="Expert" <?php echo ($skill['supervisor_rating'] === 'Expert') ? 'selected' : ''; ?>>Expert</option>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="comments_<?php echo $skill['skill_id']; ?>">
                                Feedback Comments
                            </label>
                            <textarea name="comments" id="comments_<?php echo $skill['skill_id']; ?>" rows="3" 
                                      class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                      placeholder="Provide constructive feedback..."><?php echo htmlspecialchars($skill['supervisor_comments'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" class="bg-purple-500 hover:bg-purple-600 text-white font-bold py-2 px-4 rounded transition duration-300">
                                <?php echo $skill['supervisor_rating'] ? 'Update Evaluation' : 'Submit Evaluation'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Soft Skills -->
<div class="bg-white rounded-lg shadow-md p-6 mb-8">
    <h2 class="text-xl font-semibold mb-4 text-gray-700">Soft Skills</h2>
    
    <?php if (empty($soft_skills)): ?>
        <p class="text-gray-600">No soft skills added yet.</p>
    <?php else: ?>
        <div class="space-y-6">
            <?php foreach ($soft_skills as $skill): ?>
                <div class="border rounded-lg p-4">
                    <div class="flex flex-col md:flex-row md:justify-between md:items-start mb-4">
                        <div>
                            <h3 class="font-semibold text-lg text-gray-800"><?php echo htmlspecialchars($skill['skill_name']); ?></h3>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($skill['skill_description']); ?></p>
                        </div>
                        <div class="mt-2 md:mt-0 flex flex-col md:items-end">
                            <div class="mb-2">
                                <span class="text-sm font-medium text-gray-700">Self Assessment:</span>
                                <?php 
                                    $level_color = get_level_color($skill['current_level']);
                                    $bg_color = "bg-{$level_color}-100";
                                    $text_color = "text-{$level_color}-800";
                                ?>
                                <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full <?php echo $bg_color . ' ' . $text_color; ?>">
                                    <?php echo $skill['current_level']; ?>
                                </span>
                            </div>
                            
                            <?php if ($skill['supervisor_rating']): ?>
                            <div>
                                <span class="text-sm font-medium text-gray-700">Your Evaluation:</span>
                                <?php 
                                    $sup_color = get_level_color($skill['supervisor_rating']);
                                    $sup_bg_color = "bg-{$sup_color}-100";
                                    $sup_text_color = "text-{$sup_color}-800";
                                ?>
                                <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full <?php echo $sup_bg_color . ' ' . $sup_text_color; ?>">
                                    <?php echo $skill['supervisor_rating']; ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="flex justify-between text-xs text-gray-600 mb-1">
                            <span>Beginner</span>
                            <span>Intermediate</span>
                            <span>Advanced</span>
                            <span>Expert</span>
                        </div>
                        <div class="relative h-2 bg-gray-200 rounded-full">
                            <?php 
                                $intern_progress = calculate_level_progress($skill['current_level']);
                                $color = get_level_color($skill['current_level']);
                            ?>
                            <div class="absolute h-2 bg-<?php echo $color; ?>-500 rounded-full" style="width: <?php echo $intern_progress; ?>%"></div>
                            
                            <?php if ($skill['supervisor_rating']): ?>
                                <?php 
                                    $supervisor_progress = calculate_level_progress($skill['supervisor_rating']);
                                    $sup_color = get_level_color($skill['supervisor_rating']);
                                ?>
                                <div class="absolute h-2 bg-<?php echo $sup_color; ?>-500 rounded-full opacity-50" style="width: <?php echo $supervisor_progress; ?>%"></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($skill['supervisor_comments']): ?>
                        <div class="mb-4 p-3 bg-gray-50 rounded-md">
                            <p class="text-sm font-medium text-gray-700">Your Feedback:</p>
                            <p class="text-gray-600 italic">"<?php echo htmlspecialchars($skill['supervisor_comments']); ?>"</p>
                        </div>
                    <?php endif; ?>
                    
                    <form action="evaluate_skill.php" method="POST" class="mt-4 p-4 bg-gray-50 rounded-md">
                        <input type="hidden" name="evaluate_skill" value="1">
                        <input type="hidden" name="intern_id" value="<?php echo $selected_intern_id; ?>">
                        <input type="hidden" name="skill_id" value="<?php echo $skill['skill_id']; ?>">
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="rating_<?php echo $skill['skill_id']; ?>">
                                Your Evaluation
                            </label>
                            <select name="rating" id="rating_<?php echo $skill['skill_id']; ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                <option value="">-- Select your evaluation --</option>
                                <option value="Beginner" <?php echo ($skill['supervisor_rating'] === 'Beginner') ? 'selected' : ''; ?>>Beginner</option>
                                <option value="Intermediate" <?php echo ($skill['supervisor_rating'] === 'Intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                                <option value="Advanced" <?php echo ($skill['supervisor_rating'] === 'Advanced') ? 'selected' : ''; ?>>Advanced</option>
                                <option value="Expert" <?php echo ($skill['supervisor_rating'] === 'Expert') ? 'selected' : ''; ?>>Expert</option>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="comments_<?php echo $skill['skill_id']; ?>">
                                Feedback Comments
                            </label>
                            <textarea name="comments" id="comments_<?php echo $skill['skill_id']; ?>" rows="3" 
                                      class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                      placeholder="Provide constructive feedback..."><?php echo htmlspecialchars($skill['supervisor_comments'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" class="bg-purple-500 hover:bg-purple-600 text-white font-bold py-2 px-4 rounded transition duration-300">
                                <?php echo $skill['supervisor_rating'] ? 'Update Evaluation' : 'Submit Evaluation'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Other Skills (if any) -->
<?php if (!empty($other_skills)): ?>
<div class="bg-white rounded-lg shadow-md p-6 mb-8">
    <h2 class="text-xl font-semibold mb-4 text-gray-700">Other Skills</h2>
    
    <div class="space-y-6">
        <?php foreach ($other_skills as $skill): ?>
            <div class="border rounded-lg p-4">
                <div class="flex flex-col md:flex-row md:justify-between md:items-start mb-4">
                    <div>
                        <h3 class="font-semibold text-lg text-gray-800"><?php echo htmlspecialchars($skill['skill_name']); ?></h3>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($skill['skill_description']); ?></p>
                    </div>
                    <div class="mt-2 md:mt-0 flex flex-col md:items-end">
                        <div class="mb-2">
                            <span class="text-sm font-medium text-gray-700">Self Assessment:</span>
                            <?php 
                                $level_color = get_level_color($skill['current_level']);
                                $bg_color = "bg-{$level_color}-100";
                                $text_color = "text-{$level_color}-800";
                            ?>
                            <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full <?php echo $bg_color . ' ' . $text_color; ?>">
                                <?php echo $skill['current_level']; ?>
                            </span>
                        </div>
                        
                        <?php if ($skill['supervisor_rating']): ?>
                        <div>
                            <span class="text-sm font-medium text-gray-700">Your Evaluation:</span>
                            <?php 
                                $sup_color = get_level_color($skill['supervisor_rating']);
                                $sup_bg_color = "bg-{$sup_color}-100";
                                $sup_text_color = "text-{$sup_color}-800";
                            ?>
                            <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full <?php echo $sup_bg_color . ' ' . $sup_text_color; ?>">
                                <?php echo $skill['supervisor_rating']; ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mb-4">
                    <div class="flex justify-between text-xs text-gray-600 mb-1">
                        <span>Beginner</span>
                        <span>Intermediate</span>
                        <span>Advanced</span>
                        <span>Expert</span>
                    </div>
                    <div class="relative h-2 bg-gray-200 rounded-full">
                        <?php 
                            $intern_progress = calculate_level_progress($skill['current_level']);
                            $color = get_level_color($skill['current_level']);
                        ?>
                        <div class="absolute h-2 bg-<?php echo $color; ?>-500 rounded-full" style="width: <?php echo $intern_progress; ?>%"></div>
                        
                        <?php if ($skill['supervisor_rating']): ?>
                            <?php 
                                $supervisor_progress = calculate_level_progress($skill['supervisor_rating']);
                                $sup_color = get_level_color($skill['supervisor_rating']);
                            ?>
                            <div class="absolute h-2 bg-<?php echo $sup_color; ?>-500 rounded-full opacity-50" style="width: <?php echo $supervisor_progress; ?>%"></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($skill['supervisor_comments']): ?>
                    <div class="mb-4 p-3 bg-gray-50 rounded-md">
                        <p class="text-sm font-medium text-gray-700">Your Feedback:</p>
                        <p class="text-gray-600 italic">"<?php echo htmlspecialchars($skill['supervisor_comments']); ?>"</p>
                    </div>
                <?php endif; ?>
                
                <form action="evaluate_skill.php" method="POST" class="mt-4 p-4 bg-gray-50 rounded-md">
                    <input type="hidden" name="evaluate_skill" value="1">
                    <input type="hidden" name="intern_id" value="<?php echo $selected_intern_id; ?>">
                    <input type="hidden" name="skill_id" value="<?php echo $skill['skill_id']; ?>">
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="rating_<?php echo $skill['skill_id']; ?>">
                            Your Evaluation
                        </label>
                        <select name="rating" id="rating_<?php echo $skill['skill_id']; ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                            <option value="">-- Select your evaluation --</option>
                            <option value="Beginner" <?php echo ($skill['supervisor_rating'] === 'Beginner') ? 'selected' : ''; ?>>Beginner</option>
                            <option value="Intermediate" <?php echo ($skill['supervisor_rating'] === 'Intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                            <option value="Advanced" <?php echo ($skill['supervisor_rating'] === 'Advanced') ? 'selected' : ''; ?>>Advanced</option>
                            <option value="Expert" <?php echo ($skill['supervisor_rating'] === 'Expert') ? 'selected' : ''; ?>>Expert</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="comments_<?php echo $skill['skill_id']; ?>">
                            Feedback Comments
                        </label>
                        <textarea name="comments" id="comments_<?php echo $skill['skill_id']; ?>" rows="3" 
                                  class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                  placeholder="Provide constructive feedback..."><?php echo htmlspecialchars($skill['supervisor_comments'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="bg-purple-500 hover:bg-purple-600 text-white font-bold py-2 px-4 rounded transition duration-300">
                            <?php echo $skill['supervisor_rating'] ? 'Update Evaluation' : 'Submit Evaluation'; ?>
                        </button>
                    </div>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php else: ?>
<!-- No intern selected message -->
<div class="bg-blue-50 p-6 rounded-lg shadow-md">
    <p class="text-lg text-blue-800">Please select an intern to view and evaluate their skills.</p>
</div>
<?php endif; ?>

<?php if ($selected_intern): ?>
<script>
    // Task Assignment Modal
    const assignTaskBtn = document.getElementById('assignTaskBtn');
    const assignTaskModal = document.getElementById('assignTaskModal');
    const closeTaskModalBtn = document.getElementById('closeTaskModalBtn');
    
    assignTaskBtn.addEventListener('click', () => {
        assignTaskModal.classList.remove('hidden');
    });
    
    closeTaskModalBtn.addEventListener('click', () => {
        assignTaskModal.classList.add('hidden');
    });
    
    // Close modal when clicking outside
    assignTaskModal.addEventListener('click', (e) => {
        if (e.target === assignTaskModal) {
            assignTaskModal.classList.add('hidden');
        }
    });
</script>
<?php endif; ?>

<?php
require_once "../includes/footer.php";
?>
