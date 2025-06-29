<?php
include 'db_connect.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Delete the log
    $stmt = $conn->prepare("DELETE FROM logs WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Redirect back to index with success message
        header("Location: index.php?message=Log deleted successfully");
    } else {
        // Redirect back to index with error message
        header("Location: index.php?error=Failed to delete log");
    }
    
    $stmt->close();
} else {
    // No ID provided, redirect to index
    header("Location: index.php");
}

$conn->close();
?> 