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
    $feedback_type = $_POST['feedback_type'];
    $content = trim($_POST['content']);
    $rating = $_POST['rating'];

    if (empty($content)) {
        $_SESSION['error'] = "Feedback content cannot be empty";
        header("Location: index.php");
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO feedback (intern_id, supervisor_id, feedback_type, content, rating) VALUES (:intern_id, :supervisor_id, :feedback_type, :content, :rating)");
    $stmt->bindParam(':intern_id', $intern_id);
    $stmt->bindParam(':supervisor_id', $supervisor_id);
    $stmt->bindParam(':feedback_type', $feedback_type);
    $stmt->bindParam(':content', $content);
    $stmt->bindParam(':rating', $rating);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Feedback submitted successfully";
    } else {
        $_SESSION['error'] = "Error submitting feedback";
    }

    header("Location: index.php");
    exit();
} 