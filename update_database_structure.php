<?php
require_once "config/constants.php";
require_once "config/database.php";

try {
    $database = new Database();
    $conn = $database->getConnection();

    echo "<h2>Database Structure Update for Task-Based Feedback</h2>";
    echo "<p>This script will update your database to support task-based feedback.</p>";

    // Check current feedback table structure
    $stmt = $conn->prepare("DESCRIBE feedback");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $has_feedback_type = false;
    $has_task_id = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'feedback_type') {
            $has_feedback_type = true;
        }
        if ($column['Field'] === 'task_id') {
            $has_task_id = true;
        }
    }

    echo "<h3>Current Feedback Table Structure:</h3>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li><strong>{$column['Field']}</strong>: {$column['Type']} " . ($column['Null'] === 'NO' ? '(NOT NULL)' : '(NULL)') . "</li>";
    }
    echo "</ul>";

    if ($has_feedback_type && !$has_task_id) {
        echo "<h3>Updating Database Structure...</h3>";
        
        // Step 1: Add task_id column
        echo "<p>1. Adding task_id column...</p>";
        $conn->exec("ALTER TABLE feedback ADD COLUMN task_id int(11) DEFAULT NULL AFTER supervisor_id");
        echo "<p style='color: green;'>✓ task_id column added</p>";
        
        // Step 2: Add foreign key constraint
        echo "<p>2. Adding foreign key constraint...</p>";
        $conn->exec("ALTER TABLE feedback ADD CONSTRAINT feedback_ibfk_3 FOREIGN KEY (task_id) REFERENCES mentoring_tasks(id) ON DELETE CASCADE");
        echo "<p style='color: green;'>✓ Foreign key constraint added</p>";
        
        // Step 3: Remove feedback_type column
        echo "<p>3. Removing feedback_type column...</p>";
        $conn->exec("ALTER TABLE feedback DROP COLUMN feedback_type");
        echo "<p style='color: green;'>✓ feedback_type column removed</p>";
        
        // Step 4: Make task_id NOT NULL
        echo "<p>4. Making task_id NOT NULL...</p>";
        $conn->exec("ALTER TABLE feedback MODIFY COLUMN task_id int(11) NOT NULL");
        echo "<p style='color: green;'>✓ task_id is now NOT NULL</p>";
        
        // Step 5: Add index for better performance
        echo "<p>5. Adding index for task_id...</p>";
        $conn->exec("ALTER TABLE feedback ADD INDEX idx_task_id (task_id)");
        echo "<p style='color: green;'>✓ Index added</p>";
        
        echo "<h3 style='color: green;'>✅ Database structure updated successfully!</h3>";
        
    } elseif ($has_task_id && !$has_feedback_type) {
        echo "<h3 style='color: blue;'>✅ Database already has the correct structure!</h3>";
        
    } else {
        echo "<h3 style='color: orange;'>⚠️ Unexpected table structure. Please check manually.</h3>";
    }

    // Show final structure
    echo "<h3>Final Feedback Table Structure:</h3>";
    $stmt = $conn->prepare("DESCRIBE feedback");
    $stmt->execute();
    $final_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<ul>";
    foreach ($final_columns as $column) {
        echo "<li><strong>{$column['Field']}</strong>: {$column['Type']} " . ($column['Null'] === 'NO' ? '(NOT NULL)' : '(NULL)') . "</li>";
    }
    echo "</ul>";

    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>Test the communication center feedback functionality</li>";
    echo "<li>Create some tasks for interns</li>";
    echo "<li>Try giving task-based feedback</li>";
    echo "</ol>";

} catch(PDOException $e) {
    echo "<h3 style='color: red;'>❌ Error updating database structure:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 