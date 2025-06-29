<?php
// Process task status updates from intern
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

// Check if user is an intern
if ($_SESSION['user_role'] !== 'intern') {
    $_SESSION['error'] = "Only interns can update task status.";
    header("Location: ../index.php");
    exit;
}

// Redirect if not POST request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: rename-skills_list.php");
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
$action = isset($_POST['action']) ? sanitize_input($_POST['action']) : '';

// Debug: Log the received data
error_log("=== TASK UPDATE DEBUG START ===");
error_log("User ID: $user_id");
error_log("Task ID: $task_id");
error_log("New Status: $new_status");
error_log("Action: $action");
error_log("POST data: " . print_r($_POST, true));

// Validate form data
$errors = [];
if ($task_id <= 0) {
    $errors[] = "Invalid Task ID";
    error_log("ERROR: Invalid Task ID - $task_id");
}

if (empty($new_status)) {
    $errors[] = "New status is required";
    error_log("ERROR: New status is empty");
} elseif (!in_array($new_status, ['pending', 'in_progress', 'completed'])) {
    $errors[] = "Invalid status";
    error_log("ERROR: Invalid status - $new_status");
}

// If no errors, update the task status
if (empty($errors)) {
    try {
        // First verify that this task belongs to the current user and get current status
        $check_task = $conn->prepare("
            SELECT id, status FROM mentoring_tasks 
            WHERE id = :task_id AND intern_id = :user_id
        ");
        $check_task->bindParam(':task_id', $task_id);
        $check_task->bindParam(':user_id', $user_id);
        $check_task->execute();
        
        $task_data = $check_task->fetch(PDO::FETCH_ASSOC);
        error_log("Task Check - Found rows: " . $check_task->rowCount());
        error_log("Task data: " . print_r($task_data, true));
        
        if ($task_data) {
            $current_status = $task_data['status'];
            error_log("Current status: $current_status, New status: $new_status, Action: $action");
            
            // Only allow updates from 'pending' or 'in_progress'
            if (!in_array($current_status, ['pending', 'in_progress'])) {
                $_SESSION['error'] = "You can only update tasks that are pending or in progress.";
                error_log("ERROR: Attempted to update task not in pending or in_progress");
                header("Location: rename-skills_list.php");
                exit;
            }

            // Always set to pending_confirmation when intern updates
            $final_status = 'pending_confirmation';
            $success_message = "Status changed to pending confirmation. Awaiting supervisor review.";
            
            error_log("Final status: $final_status");
            
            // Only update if status is actually changing
            if ($current_status !== $final_status) {
                // Update the task status
                $stmt = $conn->prepare("
                    UPDATE mentoring_tasks 
                    SET status = :status
                    WHERE id = :task_id AND intern_id = :user_id
                ");
                $stmt->bindParam(':status', $final_status);
                $stmt->bindParam(':task_id', $task_id);
                $stmt->bindParam(':user_id', $user_id);
                
                $result = $stmt->execute();
                error_log("Task Update - Execute result: " . ($result ? 'true' : 'false'));
                error_log("Task Update - Rows affected: " . $stmt->rowCount());
                
                if ($result && $stmt->rowCount() > 0) {
                    $_SESSION['success'] = $success_message;
                    error_log("SUCCESS: " . $success_message);
                } else {
                    $_SESSION['error'] = "Failed to update task status - no rows affected";
                    error_log("ERROR: Failed to update task status - no rows affected");
                }
            } else {
                $_SESSION['error'] = "Task is already in '$current_status' status";
                error_log("ERROR: Task is already in '$current_status' status");
            }
        } else {
            $_SESSION['error'] = "Task not found or you don't have permission to update it";
            error_log("ERROR: Task not found or you don't have permission to update it");
        }
    } catch(PDOException $e) {
        error_log("Task Update Error: " . $e->getMessage());
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = implode(", ", $errors);
    error_log("ERRORS: " . implode(", ", $errors));
}

error_log("=== TASK UPDATE DEBUG END ===");

// Redirect back to skills list
header("Location: rename-skills_list.php");
exit;
?> 