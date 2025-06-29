<?php
require_once "config/constants.php";
require_once "config/database.php";

try {
    $database = new Database();
    $conn = $database->getConnection();

    echo "Starting feedback table structure update...\n";

    // Check if feedback_type column exists
    $stmt = $conn->prepare("SHOW COLUMNS FROM feedback LIKE 'feedback_type'");
    $stmt->execute();
    $has_feedback_type = $stmt->fetch();

    if ($has_feedback_type) {
        echo "Updating feedback table structure...\n";
        
        // Add task_id column
        $conn->exec("ALTER TABLE feedback ADD COLUMN task_id INT");
        
        // Add foreign key constraint
        $conn->exec("ALTER TABLE feedback ADD CONSTRAINT fk_feedback_task FOREIGN KEY (task_id) REFERENCES mentoring_tasks(id) ON DELETE CASCADE");
        
        // Remove feedback_type column
        $conn->exec("ALTER TABLE feedback DROP COLUMN feedback_type");
        
        // Make task_id NOT NULL
        $conn->exec("ALTER TABLE feedback MODIFY COLUMN task_id INT NOT NULL");
        
        echo "Feedback table structure updated successfully!\n";
    } else {
        echo "Feedback table already has the new structure.\n";
    }

    echo "Database structure update completed!\n";

} catch(PDOException $e) {
    echo "Error updating database structure: " . $e->getMessage() . "\n";
}
?> 