<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "Database connection successful\n";
    
    // Check what users exist
    echo "\nChecking users:\n";
    $stmt = $conn->query("SELECT id, username, first_name, last_name, role FROM users WHERE role = 'intern' LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($users as $user) {
        echo "User: " . $user['first_name'] . " " . $user['last_name'] . " (ID: " . $user['id'] . ", Role: " . $user['role'] . ")\n";
    }
    
    if (empty($users)) {
        echo "No intern users found!\n";
        exit;
    }
    
    // Test with the first intern user
    $user_id = $users[0]['id'];
    echo "\nTesting with user ID: $user_id\n";
    
    $skills = get_user_skills($conn, $user_id);
    
    echo "Skills for user ID $user_id:\n";
    echo "Total skills: " . count($skills) . "\n\n";
    
    if (empty($skills)) {
        echo "No skills found for this user.\n";
    } else {
        foreach($skills as $skill) {
            echo "Skill: " . $skill['skill_name'] . "\n";
            echo "  ID: " . $skill['skill_id'] . "\n";
            echo "  Category: " . $skill['skill_category'] . "\n";
            echo "  Current Level: " . $skill['current_level'] . "\n";
            echo "  Supervisor Rating: " . ($skill['supervisor_rating'] ?? 'None') . "\n";
            echo "  Description: " . $skill['skill_description'] . "\n";
            echo "---\n";
        }
    }
    
    // Also check all skills in the database
    echo "\nAll skills in database:\n";
    $all_skills = get_all_skills($conn);
    echo "Total skills in database: " . count($all_skills) . "\n";
    foreach($all_skills as $skill) {
        echo "Skill: " . $skill['name'] . " (ID: " . $skill['id'] . ", Category: " . $skill['category'] . ")\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 