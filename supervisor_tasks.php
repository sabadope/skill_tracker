<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db_connect.php';

// Only allow supervisors
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'supervisor') {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle Approve/Reject actions
if (isset($_POST['action']) && isset($_POST['task_id'])) {
    error_log("Supervisor action: " . $_POST['action'] . " on task_id: " . $_POST['task_id']);
    $task_id = intval($_POST['task_id']);
    if ($_POST['action'] === 'approve') {
        $stmt = $conn->prepare("UPDATE mentoring_tasks SET status = 'completed', updated_at = NOW() WHERE id = ? AND supervisor_id = ? LIMIT 1");
        $stmt->bind_param('ii', $task_id, $user_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Task approved and marked as completed.';
        } else {
            $_SESSION['error'] = 'Failed to approve task.';
        }
        $stmt->close();
    } elseif ($_POST['action'] === 'reject') {
        $stmt = $conn->prepare("UPDATE mentoring_tasks SET status = 'cancelled', updated_at = NOW() WHERE id = ? AND supervisor_id = ? LIMIT 1");
        $stmt->bind_param('ii', $task_id, $user_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Task rejected.';
        } else {
            $_SESSION['error'] = 'Failed to reject task.';
        }
        $stmt->close();
    }
    header('Location: supervisor_tasks.php');
    exit();
}

// Fetch pending confirmation tasks assigned by this supervisor
$stmt = $conn->prepare("SELECT t.*, s.name AS skill_name, u.first_name, u.last_name FROM mentoring_tasks t LEFT JOIN skills s ON t.skill_id = s.id JOIN users u ON t.intern_id = u.id WHERE t.supervisor_id = ? AND t.status = 'pending_confirmation' ORDER BY t.due_date DESC");
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
    <title>Review Task Proofs</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen">
<?php include 'includes/header.php'; ?>
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-10">
        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white transition-colors duration-200">Task Proofs Awaiting Confirmation</h1>
    </div>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="mb-6 alert alert-success bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert"> <?= $_SESSION['success'] ?> </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="mb-6 alert alert-danger bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert"> <?= $_SESSION['error'] ?> </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 mb-10 transition-colors duration-200">
        <h2 class="text-xl font-semibold mb-6 text-gray-700 dark:text-gray-300 transition-colors duration-200">Pending Tasks</h2>
        <?php if (count($tasks) > 0): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white dark:bg-gray-800 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="py-3 px-5 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600">Intern</th>
                            <th class="py-3 px-5 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600">Title</th>
                            <th class="py-3 px-5 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600">Description</th>
                            <th class="py-3 px-5 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600">Skill</th>
                            <th class="py-3 px-5 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600">Due Date</th>
                            <th class="py-3 px-5 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600">Proof</th>
                            <th class="py-3 px-5 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($tasks as $task): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                                <td class="py-3 px-5 text-gray-900 dark:text-white text-base mb-1"><?= htmlspecialchars($task['first_name'] . ' ' . $task['last_name']) ?></td>
                                <td class="py-3 px-5 text-gray-600 dark:text-gray-400 text-sm"><?= htmlspecialchars($task['title']) ?></td>
                                <td class="py-3 px-5 text-gray-700 dark:text-gray-300 text-sm"><?= htmlspecialchars($task['description']) ?></td>
                                <td class="py-3 px-5 text-gray-700 dark:text-gray-300 text-sm"><?= htmlspecialchars($task['skill_name'] ?? '-') ?></td>
                                <td class="py-3 px-5 text-gray-700 dark:text-gray-300 text-sm"><?= htmlspecialchars($task['due_date']) ?></td>
                                <td class="py-3 px-5 text-gray-700 dark:text-gray-300 text-sm">
                                    <?php if (!empty($task['proof_file'])): ?>
                                        <a href="<?= htmlspecialchars($task['proof_file']) ?>" target="_blank" class="text-blue-600 underline">View File</a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-5">
                                    <form method="post" class="inline" action="supervisor_tasks.php">
                                        <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                        <button type="submit" name="action" value="approve" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded mr-2">Approve</button>
                                        <button type="submit" name="action" value="reject" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-gray-600 dark:text-gray-400 transition-colors duration-200">No tasks awaiting confirmation.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html> 