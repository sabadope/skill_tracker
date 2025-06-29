<?php
require_once "../config/constants.php";
require_once "../config/database.php";
require_once "../includes/auth.php";

if (!is_logged_in() || $_SESSION['user_role'] !== 'supervisor') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $conn = $database->getConnection();

    $supervisor_id = $_SESSION['user_id'];
    $intern_id = $_POST['intern_id'];
    $task_id = $_POST['task_id'];
    $content = trim($_POST['content']);
    $rating = $_POST['rating'];

    if (empty($content)) {
        $_SESSION['error'] = "Feedback content cannot be empty";
        header("Location: index.php");
        exit();
    }

    if (empty($task_id)) {
        $_SESSION['error'] = "Please select a task";
        header("Location: index.php");
        exit();
    }

    // Verify that the task belongs to the selected intern
    $verify_stmt = $conn->prepare("SELECT id FROM mentoring_tasks WHERE id = :task_id AND intern_id = :intern_id");
    $verify_stmt->bindParam(':task_id', $task_id);
    $verify_stmt->bindParam(':intern_id', $intern_id);
    $verify_stmt->execute();
    
    if (!$verify_stmt->fetch()) {
        $_SESSION['error'] = "Invalid task selection";
        header("Location: index.php");
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO feedback (intern_id, supervisor_id, task_id, content, rating) VALUES (:intern_id, :supervisor_id, :task_id, :content, :rating)");
    $stmt->bindParam(':intern_id', $intern_id);
    $stmt->bindParam(':supervisor_id', $supervisor_id);
    $stmt->bindParam(':task_id', $task_id);
    $stmt->bindParam(':content', $content);
    $stmt->bindParam(':rating', $rating);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Task feedback submitted successfully";
    } else {
        $_SESSION['error'] = "Error submitting feedback";
    }

    header("Location: index.php");
    exit();
} 