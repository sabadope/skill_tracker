<?php
require_once "../config/constants.php";
require_once "../config/database.php";
require_once "../includes/auth.php";

if (!is_logged_in()) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $conn = $database->getConnection();

    $sender_id = $_SESSION['user_id'];
    $receiver_id = $_POST['receiver_id'];
    $message = trim($_POST['message']);

    if (empty($message)) {
        $_SESSION['error'] = "Message cannot be empty";
        header("Location: index.php");
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (:sender_id, :receiver_id, :message)");
    $stmt->bindParam(':sender_id', $sender_id);
    $stmt->bindParam(':receiver_id', $receiver_id);
    $stmt->bindParam(':message', $message);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Message sent successfully";
    } else {
        $_SESSION['error'] = "Error sending message";
    }

    header("Location: index.php");
    exit();
} 