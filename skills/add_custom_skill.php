<?php
// Process custom skill creation
require_once "../config/database.php";
require_once "../includes/auth.php";
require_once "../includes/functions.php";

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please log in to add skills.";
    header("Location: ../index.php");
    exit;
}

// Check if user is an intern
if ($_SESSION['user_role'] !== 'intern') {
    $_SESSION['error'] = "Only interns can add skills.";
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
$skill_name = isset($_POST['custom_skill_name']) ? sanitize_input($_POST['custom_skill_name']) : '';
$skill_category = isset($_POST['custom_skill_category']) ? sanitize_input($_POST['custom_skill_category']) : '';
$skill_description = isset($_POST['custom_skill_description']) ? sanitize_input($_POST['custom_skill_description']) : '';
$level = isset($_POST['level']) ? sanitize_input($_POST['level']) : '';

// Validate form data
$errors = [];
if (empty($skill_name)) {
    $errors[] = "Skill name is required";
}

if (empty($skill_category)) {
    $errors[] = "Skill category is required";
} elseif (!in_array($skill_category, ['technical', 'soft', 'other'])) {
    $errors[] = "Invalid skill category";
}

if (empty($level)) {
    $errors[] = "Skill level is required";
} elseif (!in_array($level, ['Beginner', 'Intermediate', 'Advanced', 'Expert'])) {
    $errors[] = "Invalid skill level";
}

// If no errors, create the skill and assessment
if (empty($errors)) {
    try {
        // Start transaction
        $conn->beginTransaction();

        // Insert new skill
        $stmt = $conn->prepare("
            INSERT INTO skills (name, category, description)
            VALUES (:name, :category, :description)
        ");
        
        $stmt->bindParam(':name', $skill_name);
        $stmt->bindParam(':category', $skill_category);
        $stmt->bindParam(':description', $skill_description);
        $stmt->execute();
        
        // Get the new skill ID
        $skill_id = $conn->lastInsertId();
        
        // Create skill assessment
        $stmt = $conn->prepare("
            INSERT INTO skill_assessments (user_id, skill_id, initial_level, current_level)
            VALUES (:user_id, :skill_id, :level, :level)
        ");
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':skill_id', $skill_id);
        $stmt->bindParam(':level', $level);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['success'] = "Custom skill added successfully";
    } catch(PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $_SESSION['error'] = "Failed to add custom skill: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = implode(", ", $errors);
}

// Redirect back to skills list
header("Location: rename-skills_list.php");
exit;
?> 