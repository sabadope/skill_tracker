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
        error_log("=== UPDATE_SKILL_ASSESSMENT DEBUG ===");
        error_log("User ID: $user_id, Skill ID: $skill_id, Level: $current_level");
        
        // Check if assessment exists
        $check = $conn->prepare("SELECT id FROM skill_assessments WHERE user_id = :user_id AND skill_id = :skill_id");
        $check->bindParam(':user_id', $user_id);
        $check->bindParam(':skill_id', $skill_id);
        $check->execute();
        
        error_log("Assessment exists check: " . $check->rowCount() . " rows found");
        
        if ($check->rowCount() > 0) {
            // Update existing assessment
            error_log("Updating existing assessment");
            $stmt = $conn->prepare("
                UPDATE skill_assessments
                SET current_level = :current_level,
                    last_updated = CURRENT_TIMESTAMP
                WHERE user_id = :user_id AND skill_id = :skill_id
            ");
        } else {
            // Create new assessment
            error_log("Creating new assessment");
            $stmt = $conn->prepare("
                INSERT INTO skill_assessments (user_id, skill_id, initial_level, current_level)
                VALUES (:user_id, :skill_id, :current_level, :current_level)
            ");
        }
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':skill_id', $skill_id);
        $stmt->bindParam(':current_level', $current_level);
        
        $result = $stmt->execute();
        error_log("SQL execution result: " . ($result ? 'SUCCESS' : 'FAILED'));
        error_log("Rows affected: " . $stmt->rowCount());
        
        // Verify the update/insert
        $verify = $conn->prepare("SELECT current_level FROM skill_assessments WHERE user_id = :user_id AND skill_id = :skill_id");
        $verify->bindParam(':user_id', $user_id);
        $verify->bindParam(':skill_id', $skill_id);
        $verify->execute();
        
        if ($verify->rowCount() > 0) {
            $actual_level = $verify->fetch(PDO::FETCH_ASSOC)['current_level'];
            error_log("Verified level in database: $actual_level");
            error_log("Level match: " . ($actual_level === $current_level ? 'YES' : 'NO'));
        } else {
            error_log("ERROR: Could not verify level in database");
        }
        
        error_log("=== UPDATE_SKILL_ASSESSMENT DEBUG END ===");
        return $result;
        
    } catch(PDOException $e) {
        error_log("Database error in update_skill_assessment: " . $e->getMessage());
        return false;
    }
}

// Function to evaluate intern's skill (supervisor)
function evaluate_intern_skill($conn, $user_id, $skill_id, $supervisor_id, $rating, $comments) {
    try {
        error_log("=== EVALUATE_INTERN_SKILL DEBUG ===");
        error_log("User ID: $user_id, Skill ID: $skill_id, Supervisor ID: $supervisor_id");
        error_log("Rating: $rating, Comments: $comments");
        
        // Check if assessment exists
        $check = $conn->prepare("SELECT id FROM skill_assessments WHERE user_id = :user_id AND skill_id = :skill_id");
        $check->bindParam(':user_id', $user_id);
        $check->bindParam(':skill_id', $skill_id);
        $check->execute();
        
        error_log("Assessment exists check: " . $check->rowCount() . " rows found");
        
        if ($check->rowCount() == 0) {
            error_log("ERROR: No skill assessment found for user $user_id and skill $skill_id");
            return false;
        }
        
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
        
        $result = $stmt->execute();
        error_log("SQL execution result: " . ($result ? 'SUCCESS' : 'FAILED'));
        error_log("Rows affected: " . $stmt->rowCount());
        
        // Verify the update
        $verify = $conn->prepare("SELECT supervisor_rating, supervisor_comments FROM skill_assessments WHERE user_id = :user_id AND skill_id = :skill_id");
        $verify->bindParam(':user_id', $user_id);
        $verify->bindParam(':skill_id', $skill_id);
        $verify->execute();
        
        if ($verify->rowCount() > 0) {
            $actual_data = $verify->fetch(PDO::FETCH_ASSOC);
            error_log("Verified rating in database: " . $actual_data['supervisor_rating']);
            error_log("Verified comments in database: " . $actual_data['supervisor_comments']);
            error_log("Rating match: " . ($actual_data['supervisor_rating'] === $rating ? 'YES' : 'NO'));
        } else {
            error_log("ERROR: Could not verify evaluation in database");
        }
        
        error_log("=== EVALUATE_INTERN_SKILL DEBUG END ===");
        return $result;
        
    } catch(PDOException $e) {
        error_log("Database error in evaluate_intern_skill: " . $e->getMessage());
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

// Function to get tasks assigned by a supervisor
function get_supervisor_tasks($conn, $supervisor_id) {
    try {
        error_log("=== GET_SUPERVISOR_TASKS DEBUG ===");
        error_log("Supervisor ID: $supervisor_id");
        
        $stmt = $conn->prepare("
            SELECT mt.*, 
                   u.first_name as intern_first_name, u.last_name as intern_last_name,
                   u.department as intern_department,
                   s.name as skill_name
            FROM mentoring_tasks mt
            JOIN users u ON mt.intern_id = u.id
            LEFT JOIN skills s ON mt.skill_id = s.id
            WHERE mt.supervisor_id = :supervisor_id
            ORDER BY mt.due_date, mt.status
        ");
        $stmt->bindParam(':supervisor_id', $supervisor_id);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Found " . count($results) . " tasks for supervisor $supervisor_id");
        if (!empty($results)) {
            error_log("Sample task: " . print_r($results[0], true));
        }
        error_log("=== GET_SUPERVISOR_TASKS DEBUG END ===");
        
        return $results;
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

// Function to update task status by supervisor
function update_task_status_by_supervisor($conn, $task_id, $supervisor_id, $new_status) {
    try {
        error_log("=== UPDATE_TASK_STATUS_BY_SUPERVISOR DEBUG ===");
        error_log("Task ID: $task_id, Supervisor ID: $supervisor_id, New Status: $new_status");
        
        // Since multiple tasks have id = 0, we need to use a more specific identifier
        // We'll use a combination of fields to uniquely identify the task
        if ($task_id == 0) {
            // For tasks with id = 0, we need to use a different approach
            // We'll use the task title, intern_id, and supervisor_id to identify the specific task
            error_log("WARNING: Task ID is 0 - using alternative identification method");
            
            // Get the task details from the form (we'll need to modify the form to pass more data)
            $task_title = isset($_POST['task_title']) ? sanitize_input($_POST['task_title']) : '';
            $intern_id = isset($_POST['intern_id']) ? (int)$_POST['intern_id'] : 0;
            $due_date = isset($_POST['due_date']) ? sanitize_input($_POST['due_date']) : '';
            
            if (empty($task_title) || $intern_id == 0) {
                error_log("ERROR: Missing task identification data");
                return false;
            }
            
            // Update using multiple fields to identify the specific task
            $stmt = $conn->prepare("
                UPDATE mentoring_tasks 
                SET status = :status, updated_at = CURRENT_TIMESTAMP
                WHERE id = :task_id 
                AND supervisor_id = :supervisor_id 
                AND intern_id = :intern_id
                AND title = :task_title
                AND due_date = :due_date
            ");
            $stmt->bindParam(':status', $new_status);
            $stmt->bindParam(':task_id', $task_id);
            $stmt->bindParam(':supervisor_id', $supervisor_id);
            $stmt->bindParam(':intern_id', $intern_id);
            $stmt->bindParam(':task_title', $task_title);
            $stmt->bindParam(':due_date', $due_date);
        } else {
            // Normal update for tasks with unique IDs
            $stmt = $conn->prepare("
                UPDATE mentoring_tasks 
                SET status = :status, updated_at = CURRENT_TIMESTAMP
                WHERE id = :task_id AND supervisor_id = :supervisor_id
            ");
            $stmt->bindParam(':status', $new_status);
            $stmt->bindParam(':task_id', $task_id);
            $stmt->bindParam(':supervisor_id', $supervisor_id);
        }
        
        $result = $stmt->execute();
        error_log("Update result: " . ($result ? 'SUCCESS' : 'FAILED'));
        error_log("Rows affected: " . $stmt->rowCount());
        
        if ($stmt->rowCount() > 1) {
            error_log("WARNING: Multiple rows affected - this indicates a database structure issue");
        }
        
        error_log("=== UPDATE_TASK_STATUS_BY_SUPERVISOR DEBUG END ===");
        return $result;
        
    } catch(PDOException $e) {
        error_log("Database error in update_task_status_by_supervisor: " . $e->getMessage());
        return false;
    }
}

// Function to get skill growth analytics data
function get_skill_growth_data($conn, $department = null, $skill_id = null, $time_period = null, $limit = 8) {
    try {
        // Initial: intern self-assessment
        $initial_query = "
            SELECT s.id as skill_id, s.name as skill_name, s.category,
                   COUNT(CASE WHEN sa.initial_level = 'Beginner' THEN 1 END) as initial_beginner,
                   COUNT(CASE WHEN sa.initial_level = 'Intermediate' THEN 1 END) as initial_intermediate,
                   COUNT(CASE WHEN sa.initial_level = 'Advanced' THEN 1 END) as initial_advanced,
                COUNT(CASE WHEN sa.initial_level = 'Expert' THEN 1 END) as initial_expert
            FROM skills s
            JOIN skill_assessments sa ON s.id = sa.skill_id
            JOIN users u ON sa.user_id = u.id
            WHERE u.role = 'intern'
        ";
        $params = [];
        if ($department) {
            $initial_query .= " AND u.department = :department";
            $params[':department'] = $department;
        }
        if ($skill_id) {
            $initial_query .= " AND s.id = :skill_id";
            $params[':skill_id'] = $skill_id;
        }
        if ($time_period) {
            $initial_query .= " AND sa.last_updated >= DATE_SUB(CURRENT_DATE, INTERVAL :time_period DAY)";
            $params[':time_period'] = $time_period;
        }
        $initial_query .= " GROUP BY s.id ORDER BY s.category, s.name";
        $initial_stmt = $conn->prepare($initial_query);
        foreach ($params as $key => $value) {
            $initial_stmt->bindValue($key, $value);
        }
        $initial_stmt->execute();
        $initial_data = [];
        while ($row = $initial_stmt->fetch(PDO::FETCH_ASSOC)) {
            $initial_data[$row['skill_id']] = $row;
        }

        // Current: supervisor verdict only (exclude if supervisor_rating is NULL)
        $current_query = "
            SELECT s.id as skill_id,
                COUNT(CASE WHEN sa.supervisor_rating = 'Beginner' THEN 1 END) as current_beginner,
                COUNT(CASE WHEN sa.supervisor_rating = 'Intermediate' THEN 1 END) as current_intermediate,
                COUNT(CASE WHEN sa.supervisor_rating = 'Advanced' THEN 1 END) as current_advanced,
                COUNT(CASE WHEN sa.supervisor_rating = 'Expert' THEN 1 END) as current_expert
            FROM skills s
            JOIN skill_assessments sa ON s.id = sa.skill_id
            JOIN users u ON sa.user_id = u.id
            WHERE u.role = 'intern' AND sa.supervisor_rating IS NOT NULL
        ";
        if ($department) {
            $current_query .= " AND u.department = :department";
        }
        if ($skill_id) {
            $current_query .= " AND s.id = :skill_id";
        }
        if ($time_period) {
            $current_query .= " AND sa.last_updated >= DATE_SUB(CURRENT_DATE, INTERVAL :time_period DAY)";
        }
        $current_query .= " GROUP BY s.id ORDER BY s.category, s.name";
        $current_stmt = $conn->prepare($current_query);
        foreach ($params as $key => $value) {
            $current_stmt->bindValue($key, $value);
        }
        $current_stmt->execute();
        $current_data = [];
        while ($row = $current_stmt->fetch(PDO::FETCH_ASSOC)) {
            $current_data[$row['skill_id']] = $row;
        }

        // Merge and calculate growth
        $result = [];
        foreach ($initial_data as $skill_id => $idata) {
            $cdata = isset($current_data[$skill_id]) ? $current_data[$skill_id] : [
                'current_beginner' => 0,
                'current_intermediate' => 0,
                'current_advanced' => 0,
                'current_expert' => 0
            ];
            $initial_total = $idata['initial_beginner'] + $idata['initial_intermediate'] + $idata['initial_advanced'] + $idata['initial_expert'];
            $current_total = $cdata['current_beginner'] + $cdata['current_intermediate'] + $cdata['current_advanced'] + $cdata['current_expert'];
            $initial_advanced = $idata['initial_advanced'] + $idata['initial_expert'];
            $current_advanced = $cdata['current_advanced'] + $cdata['current_expert'];
            $initial_advanced_percent = $initial_total > 0 ? round(($initial_advanced / $initial_total) * 100) : 0;
            $current_advanced_percent = $current_total > 0 ? round(($current_advanced / $current_total) * 100) : 0;
            $growth = $current_advanced_percent - $initial_advanced_percent;
            $result[] = [
                'skill_id' => $skill_id,
                'skill_name' => $idata['skill_name'],
                'category' => $idata['category'],
                'initial_beginner' => $idata['initial_beginner'],
                'initial_intermediate' => $idata['initial_intermediate'],
                'initial_advanced' => $idata['initial_advanced'],
                'initial_expert' => $idata['initial_expert'],
                'current_beginner' => $cdata['current_beginner'],
                'current_intermediate' => $cdata['current_intermediate'],
                'current_advanced' => $cdata['current_advanced'],
                'current_expert' => $cdata['current_expert'],
                'growth' => $growth
            ];
        }
        // Sort by growth DESC
        usort($result, function($a, $b) { return $b['growth'] - $a['growth']; });
        // Limit to N skills (0, 3, 5, 7, 9, ...)
        $allowed = [0, 3, 5, 7, 9];
        $n = 0;
        foreach ($allowed as $v) { if ($v <= $limit) $n = $v; }
        if ($n > 0) $result = array_slice($result, 0, $n);
        return $result;
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
                   COUNT(CASE WHEN COALESCE(sa.supervisor_rating, sa.current_level) IN ('Advanced', 'Expert') THEN 1 END) as advanced_skills,
                   COUNT(DISTINCT sa.skill_id) as total_skills,
                   (COUNT(CASE WHEN COALESCE(sa.supervisor_rating, sa.current_level) IN ('Advanced', 'Expert') THEN 1 END) / COUNT(DISTINCT sa.skill_id)) * 100 as proficiency_percentage
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

// Department Management Functions

// Function to get all departments
function get_all_departments($conn) {
    try {
        $stmt = $conn->query("SELECT * FROM departments ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

// Function to add a new department
function add_department($conn, $name, $description = '') {
    try {
        $stmt = $conn->prepare("INSERT INTO departments (name, description) VALUES (:name, :description)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        return $stmt->execute();
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

// Function to update a department
function update_department($conn, $id, $name, $description = '') {
    try {
        $stmt = $conn->prepare("UPDATE departments SET name = :name, description = :description WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        return $stmt->execute();
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

// Function to delete a department
function delete_department($conn, $id) {
    try {
        // Check if department is being used by any users
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE department_id = :id");
        $check_stmt->bindParam(':id', $id);
        $check_stmt->execute();
        
        if ($check_stmt->fetchColumn() > 0) {
            return false; // Department is in use
        }
        
        $stmt = $conn->prepare("DELETE FROM departments WHERE id = :id");
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

// Function to get department by ID
function get_department_by_id($conn, $id) {
    try {
        $stmt = $conn->prepare("SELECT * FROM departments WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

// Function to get all skills that require attention across all interns
function get_all_skills_requiring_attention($conn) {
    try {
        $query = "
            SELECT
                s.id as skill_id,
                s.name as skill_name,
                s.category as skill_category,
                u.id as intern_id,
                u.first_name,
                u.last_name,
                sa.supervisor_rating
            FROM
                skill_assessments sa
            JOIN
                skills s ON sa.skill_id = s.id
            JOIN
                users u ON sa.user_id = u.id
            WHERE
                u.role = 'intern' AND
                sa.supervisor_rating IN ('Beginner', 'Intermediate')
            ORDER BY
                s.name, u.first_name;
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $skills_attention = [];
        foreach ($results as $row) {
            $skill_id = $row['skill_id'];
            if (!isset($skills_attention[$skill_id])) {
                $skills_attention[$skill_id] = [
                    'name' => $row['skill_name'],
                    'category' => $row['skill_category'],
                    'interns' => []
                ];
            }
            $skills_attention[$skill_id]['interns'][] = [
                'id' => $row['intern_id'],
                'name' => $row['first_name'] . ' ' . $row['last_name'],
                'level' => $row['supervisor_rating']
            ];
        }
        return $skills_attention;

    } catch(PDOException $e) {
        error_log("Database error in get_all_skills_requiring_attention: " . $e->getMessage());
        return [];
    }
}
?>
