<?php
// General utility functions for the application

// Function to sanitize user input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to get user data by ID
function get_user_by_id($conn, $user_id) {
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

// Function to get all users by role
function get_users_by_role($conn, $role) {
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE role = :role");
        $stmt->bindParam(':role', $role);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

// Function to get all skills
function get_all_skills($conn) {
    try {
        $stmt = $conn->query("SELECT * FROM skills ORDER BY category, name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

// Function to get skills by category
function get_skills_by_category($conn, $category) {
    try {
        $stmt = $conn->prepare("SELECT * FROM skills WHERE category = :category ORDER BY name");
        $stmt->bindParam(':category', $category);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

// Function to get user skills assessments
function get_user_skills($conn, $user_id) {
    try {
        $stmt = $conn->prepare("
            SELECT sa.*, s.name as skill_name, s.category as skill_category, s.description as skill_description,
                   u.first_name as supervisor_first_name, u.last_name as supervisor_last_name
            FROM skill_assessments sa
            JOIN skills s ON sa.skill_id = s.id
            LEFT JOIN users u ON sa.supervisor_id = u.id
            WHERE sa.user_id = :user_id
            ORDER BY s.category, s.name
        ");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

// Function to update skill assessment
function update_skill_assessment($conn, $user_id, $skill_id, $current_level) {
    try {
        // Check if assessment exists
        $check = $conn->prepare("SELECT id FROM skill_assessments WHERE user_id = :user_id AND skill_id = :skill_id");
        $check->bindParam(':user_id', $user_id);
        $check->bindParam(':skill_id', $skill_id);
        $check->execute();
        
        if ($check->rowCount() > 0) {
            // Update existing assessment
            $stmt = $conn->prepare("
                UPDATE skill_assessments
                SET current_level = :current_level,
                    last_updated = CURRENT_TIMESTAMP
                WHERE user_id = :user_id AND skill_id = :skill_id
            ");
        } else {
            // Create new assessment
            $stmt = $conn->prepare("
                INSERT INTO skill_assessments (user_id, skill_id, initial_level, current_level)
                VALUES (:user_id, :skill_id, :current_level, :current_level)
            ");
        }
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':skill_id', $skill_id);
        $stmt->bindParam(':current_level', $current_level);
        return $stmt->execute();
        
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

// Function to evaluate intern's skill (supervisor)
function evaluate_intern_skill($conn, $user_id, $skill_id, $supervisor_id, $rating, $comments) {
    try {
        $stmt = $conn->prepare("
            UPDATE skill_assessments
            SET supervisor_rating = :rating,
                supervisor_id = :supervisor_id,
                supervisor_comments = :comments,
                last_updated = CURRENT_TIMESTAMP
            WHERE user_id = :user_id AND skill_id = :skill_id
        ");
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':skill_id', $skill_id);
        $stmt->bindParam(':supervisor_id', $supervisor_id);
        $stmt->bindParam(':rating', $rating);
        $stmt->bindParam(':comments', $comments);
        return $stmt->execute();
        
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

// Function to create mentoring task
function create_mentoring_task($conn, $intern_id, $supervisor_id, $title, $description, $skill_id, $due_date) {
    try {
        $stmt = $conn->prepare("
            INSERT INTO mentoring_tasks 
            (intern_id, supervisor_id, title, description, skill_id, due_date)
            VALUES (:intern_id, :supervisor_id, :title, :description, :skill_id, :due_date)
        ");
        
        $stmt->bindParam(':intern_id', $intern_id);
        $stmt->bindParam(':supervisor_id', $supervisor_id);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':skill_id', $skill_id);
        $stmt->bindParam(':due_date', $due_date);
        return $stmt->execute();
        
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

// Function to get mentoring tasks for an intern
function get_intern_tasks($conn, $intern_id) {
    try {
        $stmt = $conn->prepare("
            SELECT mt.*, 
                   u.first_name as supervisor_first_name, u.last_name as supervisor_last_name,
                   s.name as skill_name
            FROM mentoring_tasks mt
            JOIN users u ON mt.supervisor_id = u.id
            LEFT JOIN skills s ON mt.skill_id = s.id
            WHERE mt.intern_id = :intern_id
            ORDER BY mt.due_date
        ");
        $stmt->bindParam(':intern_id', $intern_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

// Function to get mentoring tasks assigned by a supervisor
function get_supervisor_tasks($conn, $supervisor_id) {
    try {
        $stmt = $conn->prepare("
            SELECT mt.*, 
                   u.first_name as intern_first_name, u.last_name as intern_last_name,
                   s.name as skill_name
            FROM mentoring_tasks mt
            JOIN users u ON mt.intern_id = u.id
            LEFT JOIN skills s ON mt.skill_id = s.id
            WHERE mt.supervisor_id = :supervisor_id
            ORDER BY mt.due_date
        ");
        $stmt->bindParam(':supervisor_id', $supervisor_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

// Function to get skill growth analytics data
function get_skill_growth_data($conn, $department = null, $skill_id = null, $time_period = null) {
    try {
        $query = "
            SELECT s.name as skill_name, s.category,
                   COUNT(CASE WHEN sa.initial_level = 'Beginner' THEN 1 END) as initial_beginner,
                   COUNT(CASE WHEN sa.initial_level = 'Intermediate' THEN 1 END) as initial_intermediate,
                   COUNT(CASE WHEN sa.initial_level = 'Advanced' THEN 1 END) as initial_advanced,
                   COUNT(CASE WHEN sa.initial_level = 'Expert' THEN 1 END) as initial_expert,
                   COUNT(CASE WHEN sa.current_level = 'Beginner' THEN 1 END) as current_beginner,
                   COUNT(CASE WHEN sa.current_level = 'Intermediate' THEN 1 END) as current_intermediate,
                   COUNT(CASE WHEN sa.current_level = 'Advanced' THEN 1 END) as current_advanced,
                   COUNT(CASE WHEN sa.current_level = 'Expert' THEN 1 END) as current_expert
            FROM skills s
            JOIN skill_assessments sa ON s.id = sa.skill_id
            JOIN users u ON sa.user_id = u.id
            WHERE u.role = 'intern'
        ";
        
        $params = [];
        
        if ($department) {
            $query .= " AND u.department = :department";
            $params[':department'] = $department;
        }
        
        if ($skill_id) {
            $query .= " AND s.id = :skill_id";
            $params[':skill_id'] = $skill_id;
        }
        
        if ($time_period) {
            $query .= " AND sa.last_updated >= DATE_SUB(CURRENT_DATE, INTERVAL :time_period DAY)";
            $params[':time_period'] = $time_period;
        }
        
        $query .= " GROUP BY s.id ORDER BY s.category, s.name";
        
        $stmt = $conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

// Function to get top performing interns
function get_top_interns($conn, $limit = 5) {
    try {
        $query = "
            SELECT u.id, u.first_name, u.last_name, u.department,
                   COUNT(CASE WHEN sa.current_level IN ('Advanced', 'Expert') THEN 1 END) as advanced_skills,
                   COUNT(DISTINCT sa.skill_id) as total_skills,
                   (COUNT(CASE WHEN sa.current_level IN ('Advanced', 'Expert') THEN 1 END) / COUNT(DISTINCT sa.skill_id)) * 100 as proficiency_percentage
            FROM users u
            JOIN skill_assessments sa ON u.id = sa.user_id
            WHERE u.role = 'intern'
            GROUP BY u.id
            HAVING total_skills > 0
            ORDER BY proficiency_percentage DESC, advanced_skills DESC
            LIMIT :limit
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

// Function to identify skill gaps
function identify_skill_gaps($conn) {
    try {
        $query = "
            SELECT s.id, s.name, s.category,
                   COUNT(CASE WHEN sa.current_level = 'Beginner' THEN 1 END) as beginners,
                   COUNT(CASE WHEN sa.supervisor_rating = 'Beginner' THEN 1 END) as supervisor_beginners,
                   COUNT(sa.id) as total_assessments,
                   (COUNT(CASE WHEN sa.current_level = 'Beginner' THEN 1 END) / COUNT(sa.id)) * 100 as beginner_percentage
            FROM skills s
            JOIN skill_assessments sa ON s.id = sa.skill_id
            JOIN users u ON sa.user_id = u.id
            WHERE u.role = 'intern'
            GROUP BY s.id
            HAVING total_assessments > 0
            ORDER BY beginner_percentage DESC
        ";
        
        $stmt = $conn->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

// Function to get learning suggestions based on skill level
function get_learning_suggestions($level, $skill_name) {
    $suggestions = [
        'Beginner' => [
            'PHP Programming' => 'Complete PHP basics course on W3Schools or Codecademy. Practice with simple scripts.',
            'JavaScript' => 'Learn JavaScript fundamentals. Try FreeCodeCamp\'s JavaScript course.',
            'Database Design' => 'Study database normalization and complete MySQL tutorial.',
            'Git Version Control' => 'Learn basic Git commands: clone, commit, push, pull. Try GitHub Learning Lab.',
            'API Development' => 'Understand HTTP methods and RESTful principles through tutorials.',
            'Communication' => 'Practice daily standups and written summaries of your work.',
            'Teamwork' => 'Participate actively in team meetings and collaborate on small projects.',
            'Problem Solving' => 'Work through coding challenges on platforms like LeetCode or HackerRank.',
            'Time Management' => 'Use a task management system like Trello or Asana to track your work.',
            'Leadership' => 'Take initiative on a small project component.'
        ],
        'Intermediate' => [
            'PHP Programming' => 'Explore PHP frameworks like Laravel or Symfony. Build a small project.',
            'JavaScript' => 'Learn a JavaScript framework like React or Vue. Create interactive components.',
            'Database Design' => 'Practice complex queries, indexes, and optimization techniques.',
            'Git Version Control' => 'Learn branching strategies, resolving conflicts, and pull requests.',
            'API Development' => 'Build a full CRUD API for a personal project.',
            'Communication' => 'Present a technical topic to your team. Seek feedback on clarity.',
            'Teamwork' => 'Coordinate a small feature implementation with 2-3 team members.',
            'Problem Solving' => 'Debug complex issues in existing codebase with minimal guidance.',
            'Time Management' => 'Estimate task durations accurately and manage multiple priorities.',
            'Leadership' => 'Mentor a beginner on a specific skill. Lead a small team discussion.'
        ],
        'Advanced' => [
            'PHP Programming' => 'Optimize application performance. Contribute to open source PHP projects.',
            'JavaScript' => 'Learn advanced patterns, state management, and testing strategies.',
            'Database Design' => 'Design schemas for complex applications. Implement advanced optimization.',
            'Git Version Control' => 'Set up CI/CD pipelines. Manage complex merges and repository strategies.',
            'API Development' => 'Implement authentication, rate limiting, and advanced API features.',
            'Communication' => 'Create technical documentation. Present to clients or stakeholders.',
            'Teamwork' => 'Resolve team conflicts constructively. Facilitate effective team collaboration.',
            'Problem Solving' => 'Architect solutions for complex business requirements.',
            'Time Management' => 'Lead sprint planning. Help others prioritize effectively.',
            'Leadership' => 'Mentor junior team members. Lead project planning sessions.'
        ]
    ];
    
    // Default suggestion if specific one not available
    $default_suggestions = [
        'Beginner' => 'Complete online tutorials and practice with small projects.',
        'Intermediate' => 'Take on more complex tasks and deepen your understanding through projects.',
        'Advanced' => 'Share your knowledge by mentoring others. Explore advanced topics in this area.'
    ];
    
    if (isset($suggestions[$level][$skill_name])) {
        return $suggestions[$level][$skill_name];
    } else {
        return $default_suggestions[$level] ?? 'Continue practicing and improving your skills.';
    }
}

// Calculate Level Progress as Percentage
function calculate_level_progress($level) {
    $levels = ['Beginner' => 25, 'Intermediate' => 50, 'Advanced' => 75, 'Expert' => 100];
    return $levels[$level] ?? 0;
}

// Get level color for badges and progress bars
function get_level_color($level) {
    $colors = [
        'Beginner' => 'blue',
        'Intermediate' => 'green',
        'Advanced' => 'purple',
        'Expert' => 'red'
    ];
    return $colors[$level] ?? 'gray';
}
?>
