<?php
// Process skill updates
require_once "../config/database.php";
require_once "../includes/auth.php";
require_once "../includes/kylafunctions.php";

// Check if user is logged in and is an intern
require_role('intern');

// Redirect if not POST request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: skills_list.php");
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
header("Location: skills_list.php");
exit;
?>
