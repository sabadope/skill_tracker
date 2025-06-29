<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db_connect.php';
require_once "config/database.php";
require_once "includes/functions.php";

// Only allow interns (or admin in view mode)
require_role_with_admin_view('intern');

// Check if admin is in view mode
$is_admin_view_mode = is_admin_in_view_mode();
$admin_view_mode = get_admin_view_mode();
$selected_user_id = get_admin_selected_user_id();

$user_id = $_SESSION['user_id'];

// Handle status update (Start/In Progress)
if (isset($_POST['start_task']) && isset($_POST['task_id'])) {
    error_log("Intern action: start_task on task_id: " . $_POST['task_id']);
    $task_id = intval($_POST['task_id']);
    // Fetch current status for debugging
    $stmt_check = $conn->prepare("SELECT status FROM mentoring_tasks WHERE id = ? AND intern_id = ?");
    $stmt_check->bind_param('ii', $task_id, $user_id);
    $stmt_check->execute();
    $stmt_check->bind_result($current_status);
    if ($stmt_check->fetch()) {
        error_log("Current status of task_id $task_id: $current_status");
    } else {
        error_log("Task with id $task_id not found for intern $user_id");
    }
    $stmt_check->close();
    $stmt = $conn->prepare("UPDATE mentoring_tasks SET status = 'in_progress', updated_at = NOW() WHERE id = ? AND intern_id = ? LIMIT 1");
    $stmt->bind_param('ii', $task_id, $user_id);
    $result = $stmt->execute();
    error_log("Update result for start_task on task_id $task_id: " . ($result ? 'success' : 'fail'));
    if ($result) {
        $_SESSION['success'] = 'Task marked as In Progress.';
    } else {
        $_SESSION['error'] = 'Failed to update task status.';
    }
    $stmt->close();
    header('Location: my_tasks.php');
    exit();
}

// Handle cancel task (return to pending)
if (isset($_POST['cancel_task']) && isset($_POST['task_id'])) {
    error_log("Intern action: cancel_task on task_id: " . $_POST['task_id']);
    $task_id = intval($_POST['task_id']);
    $stmt = $conn->prepare("UPDATE mentoring_tasks SET status = 'pending', updated_at = NOW() WHERE id = ? AND intern_id = ? AND status = 'in_progress' LIMIT 1");
    $stmt->bind_param('ii', $task_id, $user_id);
    $result = $stmt->execute();
    error_log("Update result for cancel_task on task_id $task_id: " . ($result ? 'success' : 'fail'));
    if ($result) {
        $_SESSION['success'] = 'Task cancelled and returned to pending status.';
    } else {
        $_SESSION['error'] = 'Failed to cancel task.';
    }
    $stmt->close();
    header('Location: my_tasks.php');
    exit();
}

// Handle mark as completed with file upload
if (isset($_POST['complete_task']) && isset($_POST['task_id'])) {
    error_log("Intern action: complete_task on task_id: " . $_POST['task_id']);
    $task_id = intval($_POST['task_id']);
    $proof_file = null;
    if (isset($_FILES['proof_file']) && $_FILES['proof_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/proofs/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $ext = pathinfo($_FILES['proof_file']['name'], PATHINFO_EXTENSION);
        $filename = 'proof_' . $user_id . '_' . $task_id . '_' . time() . '.' . $ext;
        $target_path = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['proof_file']['tmp_name'], $target_path)) {
            $proof_file = $target_path;
        } else {
            $_SESSION['error'] = 'Failed to upload file.';
            header('Location: my_tasks.php');
            exit();
        }
    }
    if ($proof_file) {
        $stmt = $conn->prepare("UPDATE mentoring_tasks SET status = 'pending_confirmation', proof_file = ?, updated_at = NOW() WHERE id = ? AND intern_id = ? LIMIT 1");
        $stmt->bind_param('sii', $proof_file, $task_id, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE mentoring_tasks SET status = 'pending_confirmation', updated_at = NOW() WHERE id = ? AND intern_id = ? LIMIT 1");
        $stmt->bind_param('ii', $task_id, $user_id);
    }
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Task marked as completed and sent for supervisor confirmation.';
    } else {
        $_SESSION['error'] = 'Failed to update task status.';
    }
    $stmt->close();
    header('Location: my_tasks.php');
    exit();
}

// Fetch tasks for the intern
$stmt = $conn->prepare("SELECT t.*, s.name AS skill_name FROM mentoring_tasks t LEFT JOIN skills s ON t.skill_id = s.id WHERE t.intern_id = ? ORDER BY t.due_date DESC");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen">
    <?php include 'includes/header.php'; ?>
    
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 transition-colors duration-300">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <?php include_once "includes/admin_view_banner.php"; ?>

            <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white mb-10 text-center">
                <?php if ($is_admin_view_mode): ?>
                    My Tasks (Read-Only View)
                <?php else: ?>
                    My Tasks
                <?php endif; ?>
            </h1>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="mb-6 alert alert-success bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert"> <?= $_SESSION['success'] ?> </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="mb-6 alert alert-danger bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert"> <?= $_SESSION['error'] ?> </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 mb-10 transition-colors duration-200">
                <h2 class="text-xl font-semibold mb-6 text-gray-700 dark:text-gray-300 transition-colors duration-200">Assigned Tasks</h2>
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
                                    <th class="py-3 px-5 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600">Proof</th>
                                    <th class="py-3 px-5 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <?php foreach ($tasks as $task): ?>
                                    <?php error_log('Rendering task row: ' . $task['id'] . ' status: ' . $task['status']); ?>
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
                                        <td class="py-3 px-5 text-gray-700 dark:text-gray-300 text-sm">
                                            <?php if (!empty($task['proof_file'])): ?>
                                                <a href="<?= htmlspecialchars($task['proof_file']) ?>" target="_blank" class="text-blue-600 underline">View File</a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-5">
                                            <?php if ($is_admin_view_mode): ?>
                                                <!-- Read-only view for admin -->
                                                <?php if ($task['status'] === 'pending'): ?>
                                                    <div class="bg-gray-400 dark:bg-gray-600 text-white px-3 py-1 rounded cursor-not-allowed opacity-50">Start (Read-Only)</div>
                                                <?php elseif ($task['status'] === 'in_progress'): ?>
                                                    <div class="space-y-2">
                                                        <div class="text-xs text-gray-500">Upload proof to complete: (Read-Only)</div>
                                                        <div class="flex space-x-2">
                                                            <div class="bg-gray-400 dark:bg-gray-600 text-white px-3 py-1 rounded cursor-not-allowed opacity-50 text-xs">Mark as Completed (Read-Only)</div>
                                                            <div class="bg-gray-400 dark:bg-gray-600 text-white px-3 py-1 rounded cursor-not-allowed opacity-50 text-xs">Cancel (Read-Only)</div>
                                                        </div>
                                                    </div>
                                                <?php elseif ($task['status'] === 'pending_confirmation'): ?>
                                                    <span class="text-xs text-orange-600">Awaiting supervisor confirmation</span>
                                                <?php elseif ($task['status'] === 'completed'): ?>
                                                    <span class="text-xs text-green-600">Completed</span>
                                                <?php elseif ($task['status'] === 'cancelled'): ?>
                                                    <span class="text-xs text-red-600">Cancelled</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <!-- Normal interactive view for interns -->
                                                <?php if ($task['status'] === 'pending'): ?>
                                                    <form method="post" class="inline" action="my_tasks.php">
                                                        <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                                        <button type="submit" name="start_task" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded">Start</button>
                                                    </form>
                                                <?php elseif ($task['status'] === 'in_progress'): ?>
                                                    <form method="post" class="inline" action="my_tasks.php" enctype="multipart/form-data">
                                                        <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                                        <label class="block text-xs text-gray-500 mb-1">Upload proof to complete:</label>
                                                        <input type="file" name="proof_file" accept="image/*,application/pdf,.doc,.docx" class="mb-2 text-xs">
                                                        <div class="flex space-x-2">
                                                            <button type="submit" name="complete_task" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs">Mark as Completed</button>
                                                            <button type="submit" name="cancel_task" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs">Cancel</button>
                                                        </div>
                                                    </form>
                                                <?php elseif ($task['status'] === 'pending_confirmation'): ?>
                                                    <span class="text-xs text-orange-600">Awaiting supervisor confirmation</span>
                                                <?php elseif ($task['status'] === 'completed'): ?>
                                                    <span class="text-xs text-green-600">Completed</span>
                                                <?php elseif ($task['status'] === 'cancelled'): ?>
                                                    <span class="text-xs text-red-600">Cancelled</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
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
        </div>
    </div>
</body>
</html> 