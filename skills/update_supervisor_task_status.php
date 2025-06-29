<?php
// Process task status updates from supervisor
require_once "../config/database.php";
require_once "../includes/auth.php";
require_once "../includes/functions.php";

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please log in to update task status.";
    header("Location: ../index.php");
    exit;
}

// Check if user is a supervisor
if ($_SESSION['user_role'] !== 'supervisor') {
    $_SESSION['error'] = "Only supervisors can update task status.";
    header("Location: ../index.php");
    exit;
}

// Redirect if not POST request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: ../dashboards/supervisor_dashboard.php");
    exit;
}

// Get database connection
$database = new Database();
$conn = $database->getConnection();

// Get user ID
$user_id = $_SESSION['user_id'];

// Get form data
$task_id = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
$new_status = isset($_POST['new_status']) ? sanitize_input($_POST['new_status']) : '';

// Additional fields for task identification when id = 0
$task_title = isset($_POST['task_title']) ? sanitize_input($_POST['task_title']) : '';
$intern_id = isset($_POST['intern_id']) ? (int)$_POST['intern_id'] : 0;
$due_date = isset($_POST['due_date']) ? sanitize_input($_POST['due_date']) : '';

// Debug: Log the received data
error_log("=== SUPERVISOR TASK UPDATE DEBUG ===");
error_log("User ID: $user_id");
error_log("Task ID: $task_id");
error_log("New Status: $new_status");
error_log("Task Title: $task_title");
error_log("Intern ID: $intern_id");
error_log("Due Date: $due_date");
error_log("POST data: " . print_r($_POST, true));

// Validate form data
$errors = [];
if ($task_id < 0) {
    $errors[] = "Invalid Task ID";
}

if (empty($new_status)) {
    $errors[] = "New status is required";
} elseif (!in_array($new_status, ['pending', 'in_progress', 'completed', 'cancelled'])) {
    $errors[] = "Invalid status";
}

// If no errors, update the task status
if (empty($errors)) {
    $result = update_task_status_by_supervisor($conn, $task_id, $user_id, $new_status);
    
    if ($result) {
        $_SESSION['success'] = "Task status updated successfully to " . ucfirst(str_replace('_', ' ', $new_status));
    } else {
        $_SESSION['error'] = "Failed to update task status. Task not found or you don't have permission.";
    }
} else {
    $_SESSION['error'] = implode(", ", $errors);
}

// Redirect back to supervisor dashboard
header("Location: ../dashboards/supervisor_dashboard.php");
exit;
?> 