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

// Validate form data
$errors = [];
if (empty($skill_id)) {
    $errors[] = "Skill ID is required";
}

if (empty($level)) {
    $errors[] = "Skill level is required";
} elseif (!in_array($level, ['Beginner', 'Intermediate', 'Advanced', 'Expert'])) {
    $errors[] = "Invalid skill level";
}

// If no errors, update the skill
if (empty($errors)) {
    if (update_skill_assessment($conn, $user_id, $skill_id, $level)) {
        $_SESSION['success'] = "Skill updated successfully";
    } else {
        $_SESSION['error'] = "Failed to update skill";
    }
} else {
    $_SESSION['error'] = implode(", ", $errors);
}

// Redirect back to skills list
header("Location: rename-skills_list.php");
exit;
?>
