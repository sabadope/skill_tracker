<?php
require_once "config/database.php";

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Create messages table
    $conn->exec("CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // Create feedback table
    $conn->exec("CREATE TABLE IF NOT EXISTS feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        intern_id INT NOT NULL,
        supervisor_id INT NOT NULL,
        task_id INT NOT NULL,
        content TEXT NOT NULL,
        rating INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (intern_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (supervisor_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (task_id) REFERENCES mentoring_tasks(id) ON DELETE CASCADE
    )");

    echo "Tables created successfully!";
} catch(PDOException $e) {
    echo "Error creating tables: " . $e->getMessage();
}
?> 