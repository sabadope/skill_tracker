<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Only allow supervisors (or admin in view mode)
require_role_with_admin_view('supervisor');

// Check if admin is in view mode
$is_admin_view_mode = is_admin_in_view_mode();
$admin_view_mode = get_admin_view_mode();
$selected_user_id = get_admin_selected_user_id();

// Fetch all interns
$interns = [];
$sql = "SELECT id, first_name, last_name, department, email FROM users WHERE role = 'intern' ORDER BY first_name ASC, last_name ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $interns[] = $row;
    }
}

// Handle intern selection
$selected_intern_id = isset($_GET['intern_id']) ? intval($_GET['intern_id']) : 0;
$selected_intern = null;
foreach ($interns as $intern) {
    if ($intern['id'] == $selected_intern_id) {
        $selected_intern = $intern;
        break;
    }
}

// Handle form submission for assigning a task
if (isset($_POST['assign_task']) && $selected_intern_id) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $skill_id = !empty($_POST['skill_id']) ? intval($_POST['skill_id']) : null;
    $due_date = $_POST['due_date'];
    $supervisor_id = $_SESSION['user_id'];
    $status = 'pending';
    $created_at = $updated_at = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO mentoring_tasks (intern_id, supervisor_id, title, description, skill_id, due_date, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('iississss', $selected_intern_id, $supervisor_id, $title, $description, $skill_id, $due_date, $status, $created_at, $updated_at);
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Task assigned successfully!';
        $stmt->close();
        header('Location: assign_task.php?intern_id=' . $selected_intern_id);
        exit();
    } else {
        $_SESSION['error'] = 'Failed to assign task. Please try again.';
    }
    $stmt->close();
}

// Fetch tasks for the selected intern
$tasks = [];
if ($selected_intern_id) {
    $stmt = $conn->prepare("SELECT t.*, s.name AS skill_name FROM mentoring_tasks t LEFT JOIN skills s ON t.skill_id = s.id WHERE t.intern_id = ? ORDER BY t.due_date DESC");
    $stmt->bind_param('i', $selected_intern_id);
    $stmt->execute();
    $tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Task to Intern</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen">
<?php include 'includes/header.php'; ?>
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <?php include_once "includes/admin_view_banner.php"; ?>

    <div class="flex justify-between items-center mb-10">
        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white transition-colors duration-200">
            <?php if ($is_admin_view_mode): ?>
                Assign Task to Intern (Read-Only View)
            <?php else: ?>
                Assign Task to Intern
            <?php endif; ?>
        </h1>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="mb-6 alert alert-success bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert"> <?= $_SESSION['success'] ?> </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="mb-6 alert alert-danger bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert"> <?= $_SESSION['error'] ?> </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Intern Selection -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 mb-10 transition-colors duration-200">
        <h2 class="text-xl font-semibold mb-6 text-gray-700 dark:text-gray-300 transition-colors duration-200">Select Intern</h2>
        <?php if (!$is_admin_view_mode): ?>
        <form method="GET" action="assign_task.php">
            <div class="flex flex-col md:flex-row md:items-end space-y-4 md:space-y-0 md:space-x-4">
                <div class="flex-grow">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 transition-colors duration-200" for="intern_id">Intern</label>
                    <select name="intern_id" id="intern_id" class="appearance-none rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white w-full py-2 px-3 text-gray-700 dark:text-gray-300 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200" required>
                        <option value="">-- Select an intern --</option>
                        <?php foreach ($interns as $intern): ?>
                            <option value="<?= $intern['id'] ?>" <?= $selected_intern_id == $intern['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($intern['first_name'] . ' ' . $intern['last_name'] . ' (' . $intern['department'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg shadow transition duration-200">
                        View Tasks
                    </button>
                </div>
            </div>
        </form>
        <?php else: ?>
        <div class="flex flex-col md:flex-row md:items-end space-y-4 md:space-y-0 md:space-x-4">
            <div class="flex-grow">
                <label class="block text-gray-500 dark:text-gray-400 text-sm font-bold mb-2">Intern (Read-Only)</label>
                <div class="appearance-none rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white w-full py-2 px-3 text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800">
                    <?php if ($selected_intern_id): ?>
                        <?php 
                        $selected_intern_name = '';
                        foreach ($interns as $intern) {
                            if ($intern['id'] == $selected_intern_id) {
                                $selected_intern_name = $intern['first_name'] . ' ' . $intern['last_name'] . ' (' . $intern['department'] . ')';
                                break;
                            }
                        }
                        echo htmlspecialchars($selected_intern_name);
                        ?>
                    <?php else: ?>
                        No intern selected
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <div class="bg-gray-400 dark:bg-gray-600 text-white font-bold py-2 px-6 rounded-lg shadow cursor-not-allowed opacity-50">
                    View Tasks (Read-Only)
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!$selected_intern): ?>
    <!-- No intern selected message -->
    <div class="bg-blue-50 dark:bg-gray-800 border border-blue-200 dark:border-gray-700 p-8 rounded-2xl shadow-xl">
        <p class="text-lg text-blue-800 dark:text-blue-200">Please select an intern to view and assign their tasks.</p>
    </div>
    <?php endif; ?>

    <?php if ($selected_intern): ?>
    <!-- Intern Details -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 mb-10 transition-colors duration-200">
        <div class="flex flex-col md:flex-row justify-between">
            <div>
                <h2 class="text-xl font-semibold mb-2 text-gray-700 dark:text-gray-300 transition-colors duration-200"><?= htmlspecialchars($selected_intern['first_name'] . ' ' . $selected_intern['last_name']) ?></h2>
                <p class="text-gray-600 dark:text-gray-400 transition-colors duration-200">Department: <?= htmlspecialchars($selected_intern['department']) ?></p>
                <p class="text-gray-600 dark:text-gray-400 transition-colors duration-200">Email: <?= htmlspecialchars($selected_intern['email']) ?></p>
            </div>
            <div class="mt-4 md:mt-0">
                <a href="supervisor_tasks.php" class="bg-orange-600 hover:bg-orange-700 text-white font-bold py-2 px-6 rounded-lg shadow transition duration-200 inline-flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Pending Tasks
                </a>
            </div>
        </div>
    </div>

    <!-- Assign Task Form -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 mb-10 transition-colors duration-200">
        <h2 class="text-xl font-semibold mb-6 text-gray-700 dark:text-gray-300 transition-colors duration-200">Assign New Task</h2>
        <form method="post" action="assign_task.php?intern_id=<?= $selected_intern_id ?>">
            <input type="hidden" name="intern_id" value="<?= $selected_intern_id ?>">
            <div class="mb-4">
                <label for="title" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Task Title</label>
                <input type="text" class="appearance-none rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white w-full py-2 px-3 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200" id="title" name="title" required>
            </div>
            <div class="mb-4">
                <label for="description" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Description</label>
                <textarea class="appearance-none rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white w-full py-2 px-3 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200" id="description" name="description" rows="3" required></textarea>
            </div>
            <div class="mb-4">
                <label for="skill_id" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Skill Area (optional)</label>
                <select name="skill_id" id="skill_id" class="appearance-none rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white w-full py-2 px-3 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200">
                    <option value="">-- None --</option>
                    <?php
                    $skills = $conn->query("SELECT id, name, category FROM skills ORDER BY category ASC, name ASC");
                    $skill_groups = ['technical' => [], 'soft' => [], 'other' => []];
                    while ($skill = $skills->fetch_assoc()) {
                        $skill_groups[$skill['category']][] = $skill;
                    }
                    if (!empty($skill_groups['technical'])) {
                        echo '<optgroup label="Technical Skills">';
                        foreach ($skill_groups['technical'] as $skill) {
                            echo '<option value="' . $skill['id'] . '">' . htmlspecialchars($skill['name']) . '</option>';
                        }
                        echo '</optgroup>';
                    }
                    if (!empty($skill_groups['soft'])) {
                        echo '<optgroup label="Soft Skills">';
                        foreach ($skill_groups['soft'] as $skill) {
                            echo '<option value="' . $skill['id'] . '">' . htmlspecialchars($skill['name']) . '</option>';
                        }
                        echo '</optgroup>';
                    }
                    if (!empty($skill_groups['other'])) {
                        echo '<optgroup label="Other Skills">';
                        foreach ($skill_groups['other'] as $skill) {
                            echo '<option value="' . $skill['id'] . '">' . htmlspecialchars($skill['name']) . '</option>';
                        }
                        echo '</optgroup>';
                    }
                    ?>
                </select>
            </div>
            <div class="mb-4">
                <label for="due_date" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Due Date</label>
                <input type="date" class="appearance-none rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white w-full py-2 px-3 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200" id="due_date" name="due_date" required>
            </div>
            <div class="flex justify-end">
                <button type="submit" name="assign_task" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg shadow transition duration-200">Assign Task</button>
            </div>
        </form>
    </div>

    <!-- List of Existing Tasks -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 mb-10 transition-colors duration-200">
        <h2 class="text-xl font-semibold mb-6 text-gray-700 dark:text-gray-300 transition-colors duration-200">Existing Tasks for Intern</h2>
        <?php if (count($tasks) > 0): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white dark:bg-gray-800 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="py-3 px-5 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600">Title</th>
                            <th class="py-3 px-5 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600">Description</th>
                            <th class="py-3 px-5 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600">Skill</th>
                            <th class="py-3 px-5 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600">Due Date</th>
                            <th class="py-3 px-5 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($tasks as $task): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                                <td class="py-3 px-5 text-gray-900 dark:text-white text-base mb-1"><?= htmlspecialchars($task['title']) ?></td>
                                <td class="py-3 px-5 text-gray-600 dark:text-gray-400 text-sm"><?= htmlspecialchars($task['description']) ?></td>
                                <td class="py-3 px-5 text-gray-700 dark:text-gray-300 text-sm"><?= htmlspecialchars($task['skill_name'] ?? '-') ?></td>
                                <td class="py-3 px-5 text-gray-700 dark:text-gray-300 text-sm"><?= htmlspecialchars($task['due_date']) ?></td>
                                <td class="py-3 px-5">
                                    <?php 
                                        $status_colors = [
                                            'pending' => 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200',
                                            'in_progress' => 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200',
                                            'pending_confirmation' => 'bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200',
                                            'completed' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
                                            'cancelled' => 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200'
                                        ];
                                        $status_color = $status_colors[$task['status']] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200';
                                    ?>
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_color; ?>">
                                        <?= ucfirst(str_replace('_', ' ', $task['status'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-gray-600 dark:text-gray-400 transition-colors duration-200">No tasks assigned yet.</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
</body>
</html> 