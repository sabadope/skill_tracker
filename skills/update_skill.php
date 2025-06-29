<?php
// Process skill updates
require_once "../config/database.php";
require_once "../includes/auth.php";
require_once "../includes/functions.php";

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please log in to update skills.";
    header("Location: ../index.php");
    exit;
}

// Check if user is an intern
if ($_SESSION['user_role'] !== 'intern') {
    $_SESSION['error'] = "Only interns can update their skills.";
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
$skill_id = isset($_POST['skill_id']) ? (int)$_POST['skill_id'] : 0;
$level = isset($_POST['level']) ? sanitize_input($_POST['level']) : '';

// Debug logging
error_log("=== UPDATE SKILL DEBUG START ===");
error_log("User ID: $user_id");
error_log("Skill ID: $skill_id");
error_log("Level: $level");
error_log("POST data: " . print_r($_POST, true));

// Validate form data
$errors = [];
if (empty($skill_id)) {
    $errors[] = "Skill ID is required";
    error_log("ERROR: Skill ID is empty");
}

if (empty($level)) {
    $errors[] = "Skill level is required";
    error_log("ERROR: Level is empty");
} elseif (!in_array($level, ['Beginner', 'Intermediate', 'Advanced', 'Expert'])) {
    $errors[] = "Invalid skill level: $level";
    error_log("ERROR: Invalid skill level: $level");
}

// If no errors, update the skill
if (empty($errors)) {
    $result = update_skill_assessment($conn, $user_id, $skill_id, $level);
    error_log("Update skill assessment result: " . ($result ? 'SUCCESS' : 'FAILED'));
    
    if ($result) {
        $_SESSION['success'] = "Skill updated successfully to level: $level";
        error_log("SUCCESS: Skill updated to level: $level");
    } else {
        $_SESSION['error'] = "Failed to update skill";
        error_log("ERROR: Failed to update skill");
    }
} else {
    $_SESSION['error'] = implode(", ", $errors);
    error_log("VALIDATION ERRORS: " . implode(", ", $errors));
}

error_log("=== UPDATE SKILL DEBUG END ===");

// Redirect back to skills list
header("Location: rename-skills_list.php");
exit;
?>
