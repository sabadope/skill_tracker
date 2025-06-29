<?php
require_once "../config/constants.php";
require_once "../config/database.php";
require_once "../includes/auth.php";

if (!is_logged_in()) {
    header("Location: ../login.php");
    exit();
}

if (isset($_GET['id'])) {
    $database = new Database();
    $conn = $database->getConnection();
    
    $message_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];
    
    // First verify that the user owns this message (either as sender or receiver)
    $stmt = $conn->prepare("SELECT id FROM messages WHERE id = :message_id AND (sender_id = :user_id OR receiver_id = :user_id)");
    $stmt->bindParam(':message_id', $message_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // User owns this message, proceed with deletion
        $delete_stmt = $conn->prepare("DELETE FROM messages WHERE id = :message_id");
        $delete_stmt->bindParam(':message_id', $message_id);
        
        if ($delete_stmt->execute()) {
            $_SESSION['success'] = "Message deleted successfully";
        } else {
            $_SESSION['error'] = "Error deleting message";
        }
    } else {
        $_SESSION['error'] = "You don't have permission to delete this message";
    }
}

header("Location: index.php");
exit(); 