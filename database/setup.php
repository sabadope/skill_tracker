<?php
// Database setup script to create tables and initial data
require_once "../config/database.php";

$database = new Database();
$conn = $database->getConnection();

try {
    // Create users table
    $users_table = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) UNIQUE,
        first_name VARCHAR(50),
        last_name VARCHAR(50),
        role ENUM('intern', 'supervisor', 'admin') NOT NULL,
        department VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $conn->exec($users_table);
    
    // Create skills table
    $skills_table = "CREATE TABLE IF NOT EXISTS skills (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        category ENUM('technical', 'soft', 'other') NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $conn->exec($skills_table);
    
    // Create skill_assessments table
    $skill_assessments = "CREATE TABLE IF NOT EXISTS skill_assessments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        skill_id INT NOT NULL,
        initial_level ENUM('Beginner', 'Intermediate', 'Advanced', 'Expert') NOT NULL,
        current_level ENUM('Beginner', 'Intermediate', 'Advanced', 'Expert') NOT NULL,
        supervisor_rating ENUM('Beginner', 'Intermediate', 'Advanced', 'Expert'),
        supervisor_id INT,
        supervisor_comments TEXT,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE,
        FOREIGN KEY (supervisor_id) REFERENCES users(id)
    )";
    
    $conn->exec($skill_assessments);
    
    // Create mentoring_tasks table
    $mentoring_tasks = "CREATE TABLE IF NOT EXISTS mentoring_tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        intern_id INT NOT NULL,
        supervisor_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        skill_id INT,
        due_date DATE,
        status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (intern_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (supervisor_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (skill_id) REFERENCES skills(id)
    )";
    
    $conn->exec($mentoring_tasks);
    
    // Insert default admin user
    $admin_password = password_hash("admin123", PASSWORD_DEFAULT);
    $insert_admin = "INSERT INTO users (username, password, email, first_name, last_name, role, department) 
                    VALUES ('admin', '$admin_password', 'admin@example.com', 'Admin', 'User', 'admin', 'HR')
                    ON DUPLICATE KEY UPDATE username = username";
    $conn->exec($insert_admin);
    
    // Insert sample skills
    $tech_skills = [
        ['PHP Programming', 'technical', 'Ability to write clean, efficient PHP code'],
        ['JavaScript', 'technical', 'Frontend programming with JavaScript'],
        ['Database Design', 'technical', 'SQL database design and optimization'],
        ['Git Version Control', 'technical', 'Using Git for source code management'],
        ['API Development', 'technical', 'Building RESTful APIs']
    ];
    
    $soft_skills = [
        ['Communication', 'soft', 'Ability to clearly convey information'],
        ['Teamwork', 'soft', 'Working effectively with others'],
        ['Problem Solving', 'soft', 'Finding effective solutions to challenges'],
        ['Time Management', 'soft', 'Managing deadlines and priorities'],
        ['Leadership', 'soft', 'Guiding and motivating team members']
    ];
    
    $skills = array_merge($tech_skills, $soft_skills);
    
    $skills_insert = $conn->prepare("INSERT INTO skills (name, category, description) 
                               VALUES (:name, :category, :description) 
                               ON DUPLICATE KEY UPDATE name = name");
    
    foreach ($skills as $skill) {
        $skills_insert->bindParam(':name', $skill[0]);
        $skills_insert->bindParam(':category', $skill[1]);
        $skills_insert->bindParam(':description', $skill[2]);
        $skills_insert->execute();
    }
    
    echo "Database setup completed successfully. Tables created and initial data loaded.";
    
} catch(PDOException $e) {
    echo "Database setup error: " . $e->getMessage();
}
?>
