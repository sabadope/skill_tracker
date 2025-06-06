<?php
// Skills list page for interns to view and update their skills
require_once "../config/database.php";
require_once "../includes/auth.php";
require_once "../includes/functions.php";

// Check if user is logged in and is an intern
require_role('intern');

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
        
        <form action="update_skill.php" method="POST">
            <div class="mb-4">
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
</script>

<?php
require_once "../includes/footer.php";
?>
