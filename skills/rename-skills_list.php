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

// Check if user is an intern
if ($_SESSION['user_role'] !== 'intern') {
    $_SESSION['error'] = "Only interns can view their skills.";
    header("Location: ../index.php");
    exit;
}

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

// Get list of skills not yet added by the user
$user_skill_ids = array_map(function($skill) {
    return $skill['skill_id'];
}, $user_skills);

$available_skills = array_filter($all_skills, function($skill) use ($user_skill_ids) {
    return !in_array($skill['id'], $user_skill_ids);
});

// Group available skills by category
$available_technical = array_filter($available_skills, function($skill) {
    return $skill['category'] === 'technical';
});

$available_soft = array_filter($available_skills, function($skill) {
    return $skill['category'] === 'soft';
});

$available_other = array_filter($available_skills, function($skill) {
    return $skill['category'] === 'other';
});

// Include header
require_once "../includes/header.php";
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold">My Skills</h1>
    <button id="addSkillBtn" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded transition duration-300">
        Add New Skill
    </button>
</div>

<!-- Add Skill Modal -->
<div id="addSkillModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-800">Add New Skill</h2>
            <button id="closeModalBtn" class="text-gray-500 hover:text-gray-700">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form action="update_skill.php" method="POST" id="addSkillForm">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Skill Type
                </label>
                <div class="flex space-x-4 mb-2">
                    <label class="inline-flex items-center">
                        <input type="radio" name="skill_type" value="existing" class="form-radio" checked>
                        <span class="ml-2">Select Existing Skill</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="skill_type" value="custom" class="form-radio">
                        <span class="ml-2">Add Custom Skill</span>
                    </label>
                </div>
            </div>

            <!-- Existing Skills Selection -->
            <div id="existingSkillSection" class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="skill_id">
                    Select Skill
                </label>
                <select name="skill_id" id="skill_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
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
            </div>

            <!-- Custom Skill Input -->
            <div id="customSkillSection" class="mb-4 hidden">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="custom_skill_name">
                        Skill Name
                    </label>
                    <input type="text" name="custom_skill_name" id="custom_skill_name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Enter skill name">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="custom_skill_category">
                        Category
                    </label>
                    <select name="custom_skill_category" id="custom_skill_category" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="technical">Technical</option>
                        <option value="soft">Soft Skills</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="custom_skill_description">
                        Description
                    </label>
                    <textarea name="custom_skill_description" id="custom_skill_description" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Describe your skill"></textarea>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="level">
                    Current Level
                </label>
                <select name="level" id="level" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    <option value="">-- Select your current level --</option>
                    <option value="Beginner">Beginner</option>
                    <option value="Intermediate">Intermediate</option>
                    <option value="Advanced">Advanced</option>
                    <option value="Expert">Expert</option>
                </select>
            </div>
            
            <div class="flex justify-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded transition duration-300">
                    Add Skill
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Skill Sections -->
<div class="space-y-8">
    <!-- Technical Skills -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4 text-gray-700">Technical Skills</h2>
        
        <?php if (empty($technical_skills)): ?>
            <p class="text-gray-600">No technical skills added yet.</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($technical_skills as $skill): ?>
                    <div class="border rounded-lg p-4">
                        <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-2">
                            <div>
                                <h3 class="font-semibold text-lg text-gray-800"><?php echo htmlspecialchars($skill['skill_name']); ?></h3>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($skill['skill_description']); ?></p>
                            </div>
                            <div class="mt-2 md:mt-0">
                                <?php 
                                    $level_color = get_level_color($skill['current_level']);
                                    $bg_color = "bg-{$level_color}-100";
                                    $text_color = "text-{$level_color}-800";
                                ?>
                                <span class="px-3 py-1 inline-flex text-sm font-semibold rounded-full <?php echo $bg_color . ' ' . $text_color; ?>">
                                    <?php echo $skill['current_level']; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <div class="flex justify-between text-xs text-gray-600 mb-1">
                                <span>Beginner</span>
                                <span>Intermediate</span>
                                <span>Advanced</span>
                                <span>Expert</span>
                            </div>
                            <div class="h-2 bg-gray-200 rounded-full">
                                <?php 
                                    $progress = calculate_level_progress($skill['current_level']);
                                    $color = get_level_color($skill['current_level']);
                                ?>
                                <div class="h-2 bg-<?php echo $color; ?>-500 rounded-full" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                        </div>
                        
                        <?php if ($skill['supervisor_rating']): ?>
                        <div class="mt-3 pt-3 border-t border-gray-200">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-sm font-medium text-gray-700">Supervisor Assessment:</p>
                                    <?php 
                                        $sup_color = get_level_color($skill['supervisor_rating']);
                                        $sup_bg_color = "bg-{$sup_color}-100";
                                        $sup_text_color = "text-{$sup_color}-800";
                                    ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $sup_bg_color . ' ' . $sup_text_color; ?>">
                                        <?php echo $skill['supervisor_rating']; ?>
                                    </span>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?php if ($skill['supervisor_first_name']): ?>
                                        Evaluated by: <?php echo htmlspecialchars($skill['supervisor_first_name'] . ' ' . $skill['supervisor_last_name']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($skill['supervisor_comments']): ?>
                                <div class="mt-2 text-sm">
                                    <p class="font-medium text-gray-700">Feedback:</p>
                                    <p class="text-gray-600 italic">"<?php echo htmlspecialchars($skill['supervisor_comments']); ?>"</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mt-3 flex justify-between items-center pt-3 border-t border-gray-200">
                            <div class="text-sm">
                                <span class="text-gray-500">Last updated: <?php echo date('M d, Y', strtotime($skill['last_updated'])); ?></span>
                            </div>
                            <form action="update_skill.php" method="POST" class="flex space-x-2">
                                <input type="hidden" name="skill_id" value="<?php echo $skill['skill_id']; ?>">
                                <select name="level" class="text-sm border border-gray-300 rounded py-1 px-2">
                                    <option value="Beginner" <?php echo ($skill['current_level'] === 'Beginner') ? 'selected' : ''; ?>>Beginner</option>
                                    <option value="Intermediate" <?php echo ($skill['current_level'] === 'Intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                                    <option value="Advanced" <?php echo ($skill['current_level'] === 'Advanced') ? 'selected' : ''; ?>>Advanced</option>
                                    <option value="Expert" <?php echo ($skill['current_level'] === 'Expert') ? 'selected' : ''; ?>>Expert</option>
                                </select>
                                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white text-sm py-1 px-3 rounded">
                                    Update
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Soft Skills -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4 text-gray-700">Soft Skills</h2>
        
        <?php if (empty($soft_skills)): ?>
            <p class="text-gray-600">No soft skills added yet.</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($soft_skills as $skill): ?>
                    <div class="border rounded-lg p-4">
                        <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-2">
                            <div>
                                <h3 class="font-semibold text-lg text-gray-800"><?php echo htmlspecialchars($skill['skill_name']); ?></h3>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($skill['skill_description']); ?></p>
                            </div>
                            <div class="mt-2 md:mt-0">
                                <?php 
                                    $level_color = get_level_color($skill['current_level']);
                                    $bg_color = "bg-{$level_color}-100";
                                    $text_color = "text-{$level_color}-800";
                                ?>
                                <span class="px-3 py-1 inline-flex text-sm font-semibold rounded-full <?php echo $bg_color . ' ' . $text_color; ?>">
                                    <?php echo $skill['current_level']; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <div class="flex justify-between text-xs text-gray-600 mb-1">
                                <span>Beginner</span>
                                <span>Intermediate</span>
                                <span>Advanced</span>
                                <span>Expert</span>
                            </div>
                            <div class="h-2 bg-gray-200 rounded-full">
                                <?php 
                                    $progress = calculate_level_progress($skill['current_level']);
                                    $color = get_level_color($skill['current_level']);
                                ?>
                                <div class="h-2 bg-<?php echo $color; ?>-500 rounded-full" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                        </div>
                        
                        <?php if ($skill['supervisor_rating']): ?>
                        <div class="mt-3 pt-3 border-t border-gray-200">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-sm font-medium text-gray-700">Supervisor Assessment:</p>
                                    <?php 
                                        $sup_color = get_level_color($skill['supervisor_rating']);
                                        $sup_bg_color = "bg-{$sup_color}-100";
                                        $sup_text_color = "text-{$sup_color}-800";
                                    ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $sup_bg_color . ' ' . $sup_text_color; ?>">
                                        <?php echo $skill['supervisor_rating']; ?>
                                    </span>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?php if ($skill['supervisor_first_name']): ?>
                                        Evaluated by: <?php echo htmlspecialchars($skill['supervisor_first_name'] . ' ' . $skill['supervisor_last_name']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($skill['supervisor_comments']): ?>
                                <div class="mt-2 text-sm">
                                    <p class="font-medium text-gray-700">Feedback:</p>
                                    <p class="text-gray-600 italic">"<?php echo htmlspecialchars($skill['supervisor_comments']); ?>"</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mt-3 flex justify-between items-center pt-3 border-t border-gray-200">
                            <div class="text-sm">
                                <span class="text-gray-500">Last updated: <?php echo date('M d, Y', strtotime($skill['last_updated'])); ?></span>
                            </div>
                            <form action="update_skill.php" method="POST" class="flex space-x-2">
                                <input type="hidden" name="skill_id" value="<?php echo $skill['skill_id']; ?>">
                                <select name="level" class="text-sm border border-gray-300 rounded py-1 px-2">
                                    <option value="Beginner" <?php echo ($skill['current_level'] === 'Beginner') ? 'selected' : ''; ?>>Beginner</option>
                                    <option value="Intermediate" <?php echo ($skill['current_level'] === 'Intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                                    <option value="Advanced" <?php echo ($skill['current_level'] === 'Advanced') ? 'selected' : ''; ?>>Advanced</option>
                                    <option value="Expert" <?php echo ($skill['current_level'] === 'Expert') ? 'selected' : ''; ?>>Expert</option>
                                </select>
                                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white text-sm py-1 px-3 rounded">
                                    Update
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Other Skills (if any) -->
    <?php if (!empty($other_skills)): ?>
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4 text-gray-700">Other Skills</h2>
        
        <div class="space-y-4">
            <?php foreach ($other_skills as $skill): ?>
                <div class="border rounded-lg p-4">
                    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-2">
                        <div>
                            <h3 class="font-semibold text-lg text-gray-800"><?php echo htmlspecialchars($skill['skill_name']); ?></h3>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($skill['skill_description']); ?></p>
                        </div>
                        <div class="mt-2 md:mt-0">
                            <?php 
                                $level_color = get_level_color($skill['current_level']);
                                $bg_color = "bg-{$level_color}-100";
                                $text_color = "text-{$level_color}-800";
                            ?>
                            <span class="px-3 py-1 inline-flex text-sm font-semibold rounded-full <?php echo $bg_color . ' ' . $text_color; ?>">
                                <?php echo $skill['current_level']; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <div class="flex justify-between text-xs text-gray-600 mb-1">
                            <span>Beginner</span>
                            <span>Intermediate</span>
                            <span>Advanced</span>
                            <span>Expert</span>
                        </div>
                        <div class="h-2 bg-gray-200 rounded-full">
                            <?php 
                                $progress = calculate_level_progress($skill['current_level']);
                                $color = get_level_color($skill['current_level']);
                            ?>
                            <div class="h-2 bg-<?php echo $color; ?>-500 rounded-full" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                    </div>
                    
                    <?php if ($skill['supervisor_rating']): ?>
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm font-medium text-gray-700">Supervisor Assessment:</p>
                                <?php 
                                    $sup_color = get_level_color($skill['supervisor_rating']);
                                    $sup_bg_color = "bg-{$sup_color}-100";
                                    $sup_text_color = "text-{$sup_color}-800";
                                ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $sup_bg_color . ' ' . $sup_text_color; ?>">
                                    <?php echo $skill['supervisor_rating']; ?>
                                </span>
                            </div>
                            <div class="text-sm text-gray-500">
                                <?php if ($skill['supervisor_first_name']): ?>
                                    Evaluated by: <?php echo htmlspecialchars($skill['supervisor_first_name'] . ' ' . $skill['supervisor_last_name']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($skill['supervisor_comments']): ?>
                            <div class="mt-2 text-sm">
                                <p class="font-medium text-gray-700">Feedback:</p>
                                <p class="text-gray-600 italic">"<?php echo htmlspecialchars($skill['supervisor_comments']); ?>"</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-3 flex justify-between items-center pt-3 border-t border-gray-200">
                        <div class="text-sm">
                            <span class="text-gray-500">Last updated: <?php echo date('M d, Y', strtotime($skill['last_updated'])); ?></span>
                        </div>
                        <form action="update_skill.php" method="POST" class="flex space-x-2">
                            <input type="hidden" name="skill_id" value="<?php echo $skill['skill_id']; ?>">
                            <select name="level" class="text-sm border border-gray-300 rounded py-1 px-2">
                                <option value="Beginner" <?php echo ($skill['current_level'] === 'Beginner') ? 'selected' : ''; ?>>Beginner</option>
                                <option value="Intermediate" <?php echo ($skill['current_level'] === 'Intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                                <option value="Advanced" <?php echo ($skill['current_level'] === 'Advanced') ? 'selected' : ''; ?>>Advanced</option>
                                <option value="Expert" <?php echo ($skill['current_level'] === 'Expert') ? 'selected' : ''; ?>>Expert</option>
                            </select>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white text-sm py-1 px-3 rounded">
                                Update
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
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
    
    addSkillBtn.addEventListener('click', () => {
        addSkillModal.classList.remove('hidden');
    });
    
    closeModalBtn.addEventListener('click', () => {
        addSkillModal.classList.add('hidden');
    });
    
    // Close modal when clicking outside
    addSkillModal.addEventListener('click', (e) => {
        if (e.target === addSkillModal) {
            addSkillModal.classList.add('hidden');
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
