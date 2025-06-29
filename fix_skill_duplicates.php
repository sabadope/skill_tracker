<?php
// Script to fix duplicate skill assessments and add unique constraint
require_once "config/database.php";

// Get database connection
$database = new Database();
$conn = $database->getConnection();

try {
    echo "Starting to fix duplicate skill assessments...\n";
    
    // Start transaction
    $conn->beginTransaction();
    
    // 1. Find and remove duplicate skill assessments, keeping the most recent one
    echo "Finding duplicate skill assessments...\n";
    
    // First, let's see what duplicates exist
    $find_duplicates = "
        SELECT user_id, skill_id, COUNT(*) as count
        FROM skill_assessments 
        GROUP BY user_id, skill_id 
        HAVING COUNT(*) > 1
    ";
    
    $stmt = $conn->prepare($find_duplicates);
    $stmt->execute();
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($duplicates) . " duplicate combinations.\n";
    
    // For each duplicate combination, keep only the most recent assessment
    foreach ($duplicates as $duplicate) {
        $user_id = $duplicate['user_id'];
        $skill_id = $duplicate['skill_id'];
        
        // Get all assessments for this user-skill combination, ordered by last_updated
        $get_assessments = "
            SELECT id, last_updated 
            FROM skill_assessments 
            WHERE user_id = :user_id AND skill_id = :skill_id 
            ORDER BY last_updated DESC
        ";
        
        $stmt = $conn->prepare($get_assessments);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':skill_id', $skill_id);
        $stmt->execute();
        $assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Keep the first (most recent) one, delete the rest
        if (count($assessments) > 1) {
            $ids_to_delete = array_slice(array_column($assessments, 'id'), 1);
            $placeholders = str_repeat('?,', count($ids_to_delete) - 1) . '?';
            
            $delete_duplicates = "
                DELETE FROM skill_assessments 
                WHERE id IN ($placeholders)
            ";
            
            $stmt = $conn->prepare($delete_duplicates);
            $stmt->execute($ids_to_delete);
            
            echo "Removed " . count($ids_to_delete) . " duplicate assessments for user $user_id, skill $skill_id.\n";
        }
    }
    
    // 2. Add unique constraint to prevent future duplicates
    echo "Adding unique constraint to skill_assessments table...\n";
    
    // First, check if the unique constraint already exists
    $check_constraint = "
        SELECT COUNT(*) as constraint_exists
        FROM information_schema.table_constraints 
        WHERE constraint_schema = DATABASE()
        AND table_name = 'skill_assessments' 
        AND constraint_name = 'unique_user_skill'
    ";
    
    $stmt = $conn->prepare($check_constraint);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['constraint_exists'] == 0) {
        // Add unique constraint
        $add_constraint = "
            ALTER TABLE skill_assessments 
            ADD CONSTRAINT unique_user_skill 
            UNIQUE (user_id, skill_id)
        ";
        
        $stmt = $conn->prepare($add_constraint);
        $stmt->execute();
        echo "Unique constraint added successfully.\n";
    } else {
        echo "Unique constraint already exists.\n";
    }
    
    // 3. Update skills table to ensure category enum is correct
    echo "Checking skills table category enum...\n";
    
    // Check if 'other' category exists in skills table
    $check_other_category = "
        SELECT COUNT(*) as other_count 
        FROM skills 
        WHERE category = 'other'
    ";
    
    $stmt = $conn->prepare($check_other_category);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['other_count'] > 0) {
        echo "Found {$result['other_count']} skills with 'other' category. Converting to 'soft'...\n";
        
        // Convert 'other' category to 'soft'
        $update_other = "
            UPDATE skills 
            SET category = 'soft' 
            WHERE category = 'other'
        ";
        
        $stmt = $conn->prepare($update_other);
        $stmt->execute();
        echo "Converted 'other' category skills to 'soft'.\n";
    }
    
    // Commit transaction
    $conn->commit();
    
    echo "Successfully fixed duplicate skill assessments and updated database constraints.\n";
    echo "The system will now prevent duplicate skill assessments for the same user and skill.\n";
    
} catch(PDOException $e) {
    // Rollback transaction on error
    $conn->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
    echo "Transaction rolled back.\n";
}
?> 