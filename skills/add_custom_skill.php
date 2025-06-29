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

// Debug logging
error_log("=== CUSTOM SKILL DEBUG START ===");
error_log("User ID: $user_id");
error_log("Skill Name: $skill_name");
error_log("Skill Category: $skill_category");
error_log("Skill Description: $skill_description");
error_log("Level: $level");
error_log("POST data: " . print_r($_POST, true));

// Validate form data
$errors = [];
if (empty($skill_name)) {
    $errors[] = "Skill name is required";
}

if (empty($skill_category)) {
    $errors[] = "Skill category is required";
} elseif (!in_array($skill_category, ['technical', 'soft'])) {
    $errors[] = "Invalid skill category. Only 'technical' and 'soft' are allowed.";
}

if (empty($level)) {
    $errors[] = "Skill level is required";
    error_log("ERROR: Level is empty");
} elseif (!in_array($level, ['Beginner', 'Intermediate', 'Advanced', 'Expert'])) {
    $errors[] = "Invalid skill level: $level";
    error_log("ERROR: Invalid skill level: $level");
}

// If no errors, create the skill and assessment
if (empty($errors)) {
    try {
        // Start transaction
        $conn->beginTransaction();

        // Check if skill already exists with the same name
        $check_skill = $conn->prepare("SELECT id FROM skills WHERE name = :name");
        $check_skill->bindParam(':name', $skill_name);
        $check_skill->execute();
        
        if ($check_skill->rowCount() > 0) {
            // Skill already exists, get its ID
            $skill_id = $check_skill->fetch(PDO::FETCH_ASSOC)['id'];
            error_log("Skill already exists with ID: $skill_id");
        } else {
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
            error_log("New skill created with ID: $skill_id");
        }
        
        // Check if user already has an assessment for this skill
        $check_assessment = $conn->prepare("SELECT id FROM skill_assessments WHERE user_id = :user_id AND skill_id = :skill_id");
        $check_assessment->bindParam(':user_id', $user_id);
        $check_assessment->bindParam(':skill_id', $skill_id);
        $check_assessment->execute();
        
        if ($check_assessment->rowCount() > 0) {
            // Update the skill level instead of blocking
            $stmt = $conn->prepare("
                UPDATE skill_assessments
                SET current_level = :level, initial_level = :level
                WHERE user_id = :user_id AND skill_id = :skill_id
            ");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':skill_id', $skill_id);
            $stmt->bindParam(':level', $level);
            $result = $stmt->execute();
            if ($result) {
                $_SESSION['success'] = "Skill level updated successfully to: $level";
                error_log("SUCCESS: Skill level updated to: $level");
            } else {
                $_SESSION['error'] = "Failed to update skill level";
                error_log("ERROR: Failed to update skill level");
            }
        } else {
            // Create skill assessment
            $stmt = $conn->prepare("
                INSERT INTO skill_assessments (user_id, skill_id, initial_level, current_level)
                VALUES (:user_id, :skill_id, :level, :level)
            ");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':skill_id', $skill_id);
            $stmt->bindParam(':level', $level);
            $result = $stmt->execute();
            error_log("Skill assessment insert result: " . ($result ? 'SUCCESS' : 'FAILED'));
            error_log("Skill assessment insert rows affected: " . $stmt->rowCount());
            if ($result) {
                $_SESSION['success'] = "Custom skill added successfully with level: $level";
                error_log("SUCCESS: Custom skill added with level: $level");
            } else {
                $_SESSION['error'] = "Failed to add skill assessment";
                error_log("ERROR: Failed to add skill assessment");
            }
        }
        
        // Commit transaction
        $conn->commit();
    } catch(PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $_SESSION['error'] = "Failed to add custom skill: " . $e->getMessage();
        error_log("DATABASE ERROR: " . $e->getMessage());
    }
} else {
    $_SESSION['error'] = implode(", ", $errors);
    error_log("VALIDATION ERRORS: " . implode(", ", $errors));
}

error_log("=== CUSTOM SKILL DEBUG END ===");

// Redirect back to skills list
header("Location: rename-skills_list.php");
exit;
?> 