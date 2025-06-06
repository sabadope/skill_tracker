<?php
// This file creates a local SQLite database for demo purposes
// In a production environment, you would use MySQL via PHPMyAdmin as specified

try {
    // Create or open the SQLite database
    $db_file = __DIR__ . '/skill_tracker.sqlite';
    $pdo = new PDO('sqlite:' . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Enable foreign keys
    $pdo->exec('PRAGMA foreign_keys = ON;');
    
    // Create users table
    $pdo->exec('CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        email TEXT UNIQUE,
        first_name TEXT,
        last_name TEXT,
        role TEXT NOT NULL,
        department TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )');
    
    // Create skills table
    $pdo->exec('CREATE TABLE IF NOT EXISTS skills (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        category TEXT NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )');
    
    // Create skill_assessments table
    $pdo->exec('CREATE TABLE IF NOT EXISTS skill_assessments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        skill_id INTEGER NOT NULL,
        initial_level TEXT NOT NULL,
        current_level TEXT NOT NULL,
        supervisor_rating TEXT,
        supervisor_id INTEGER,
        supervisor_comments TEXT,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE,
        FOREIGN KEY (supervisor_id) REFERENCES users(id)
    )');
    
    // Create mentoring_tasks table
    $pdo->exec('CREATE TABLE IF NOT EXISTS mentoring_tasks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        intern_id INTEGER NOT NULL,
        supervisor_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        description TEXT,
        skill_id INTEGER,
        due_date DATE,
        status TEXT DEFAULT "pending",
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (intern_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (supervisor_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (skill_id) REFERENCES skills(id)
    )');
    
    // Insert default admin user
    $admin_exists = $pdo->query("SELECT id FROM users WHERE username = 'admin'")->fetchColumn();
    
    if (!$admin_exists) {
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, first_name, last_name, role, department) 
                        VALUES ('admin', :password, 'admin@example.com', 'Admin', 'User', 'admin', 'HR')");
        $stmt->bindParam(':password', $admin_password);
        $stmt->execute();
        
        echo "Admin user created successfully. Password hash: $admin_password\n";
    } else {
        echo "Admin user already exists.\n";
    }
    
    // Insert sample supervisor user
    $supervisor_exists = $pdo->query("SELECT id FROM users WHERE username = 'supervisor'")->fetchColumn();
    
    if (!$supervisor_exists) {
        $supervisor_password = password_hash('supervisor123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, first_name, last_name, role, department) 
                        VALUES ('supervisor', :password, 'supervisor@example.com', 'Super', 'Visor', 'supervisor', 'IT')");
        $stmt->bindParam(':password', $supervisor_password);
        $stmt->execute();
        
        echo "Supervisor user created successfully.\n";
    }
    
    // Insert sample intern user
    $intern_exists = $pdo->query("SELECT id FROM users WHERE username = 'intern'")->fetchColumn();
    
    if (!$intern_exists) {
        $intern_password = password_hash('intern123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, first_name, last_name, role, department) 
                        VALUES ('intern', :password, 'intern@example.com', 'Intern', 'User', 'intern', 'IT')");
        $stmt->bindParam(':password', $intern_password);
        $stmt->execute();
        
        echo "Intern user created successfully.\n";
    }
    
    // Insert sample technical skills
    $technical_skills = [
        ['PHP Programming', 'technical', 'Ability to write clean, efficient PHP code'],
        ['JavaScript', 'technical', 'Frontend programming with JavaScript'],
        ['Database Design', 'technical', 'SQL database design and optimization'],
        ['Git Version Control', 'technical', 'Using Git for source code management'],
        ['API Development', 'technical', 'Building RESTful APIs']
    ];
    
    foreach ($technical_skills as $skill) {
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO skills (name, category, description) VALUES (?, ?, ?)");
        $stmt->execute($skill);
    }
    
    // Insert sample soft skills
    $soft_skills = [
        ['Communication', 'soft', 'Ability to clearly convey information'],
        ['Teamwork', 'soft', 'Working effectively with others'],
        ['Problem Solving', 'soft', 'Finding effective solutions to challenges'],
        ['Time Management', 'soft', 'Managing deadlines and priorities'],
        ['Leadership', 'soft', 'Guiding and motivating team members']
    ];
    
    foreach ($soft_skills as $skill) {
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO skills (name, category, description) VALUES (?, ?, ?)");
        $stmt->execute($skill);
    }
    
    echo "Sample skills created successfully.\n";
    
    // Sample skill assessments for intern (assuming intern's id is 3)
    $intern_id = $pdo->query("SELECT id FROM users WHERE username = 'intern'")->fetchColumn();
    $supervisor_id = $pdo->query("SELECT id FROM users WHERE username = 'supervisor'")->fetchColumn();
    
    if ($intern_id && $supervisor_id) {
        // Get skill IDs
        $php_skill_id = $pdo->query("SELECT id FROM skills WHERE name = 'PHP Programming'")->fetchColumn();
        $js_skill_id = $pdo->query("SELECT id FROM skills WHERE name = 'JavaScript'")->fetchColumn();
        $comm_skill_id = $pdo->query("SELECT id FROM skills WHERE name = 'Communication'")->fetchColumn();
        $team_skill_id = $pdo->query("SELECT id FROM skills WHERE name = 'Teamwork'")->fetchColumn();
        $db_skill_id = $pdo->query("SELECT id FROM skills WHERE name = 'Database Design'")->fetchColumn();
        
        // Insert sample skill assessments
        $assessments = [
            [$intern_id, $php_skill_id, 'Beginner', 'Intermediate'],
            [$intern_id, $js_skill_id, 'Beginner', 'Beginner'],
            [$intern_id, $comm_skill_id, 'Intermediate', 'Advanced'],
            [$intern_id, $team_skill_id, 'Beginner', 'Intermediate']
        ];
        
        foreach ($assessments as $assessment) {
            $stmt = $pdo->prepare("INSERT OR IGNORE INTO skill_assessments (user_id, skill_id, initial_level, current_level) VALUES (?, ?, ?, ?)");
            $stmt->execute($assessment);
        }
        
        // Insert supervisor evaluations
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO skill_assessments (user_id, skill_id, initial_level, current_level, supervisor_id, supervisor_rating, supervisor_comments) 
                       VALUES (?, ?, 'Beginner', 'Intermediate', ?, 'Beginner', 'Needs more practice with complex queries')");
        $stmt->execute([$intern_id, $db_skill_id, $supervisor_id]);
        
        echo "Sample skill assessments created successfully.\n";
        
        // Insert sample mentoring tasks
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO mentoring_tasks (intern_id, supervisor_id, title, description, skill_id, due_date, status) 
                       VALUES (?, ?, 'Complete JavaScript Tutorial', 'Finish the JavaScript fundamentals course on Codecademy', ?, date('now', '+14 days'), 'pending')");
        $stmt->execute([$intern_id, $supervisor_id, $js_skill_id]);
        
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO mentoring_tasks (intern_id, supervisor_id, title, description, skill_id, due_date, status) 
                       VALUES (?, ?, 'Database Optimization Project', 'Optimize queries and index structure for the product catalog database', ?, date('now', '+30 days'), 'pending')");
        $stmt->execute([$intern_id, $supervisor_id, $db_skill_id]);
        
        echo "Sample mentoring tasks created successfully.\n";
    }
    
    echo "SQLite demo database created successfully. Location: " . $db_file . "\n";
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}