<?php
// Authentication functions for login, logout, and session management

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Function to check if user has the specified role
function has_role($role) {
    if (!is_logged_in()) {
        return false;
    }
    
    if (is_array($role)) {
        return in_array($_SESSION['user_role'], $role);
    } else {
        return $_SESSION['user_role'] === $role;
    }
}

// Function to authenticate a user
function authenticate_user($conn, $username, $password) {
    try {
        $stmt = $conn->prepare("SELECT id, username, password, first_name, last_name, role FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() === 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_role'] = $user['role'];
                return true;
            }
        }
        
        return false;
    } catch(PDOException $e) {
        error_log("Authentication error: " . $e->getMessage());
        return false;
    }
}

// Function to register a new user
function register_user($conn, $username, $password, $email, $first_name, $last_name, $role, $department) {
    try {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("
            INSERT INTO users (username, password, email, first_name, last_name, role, department)
            VALUES (:username, :password, :email, :first_name, :last_name, :role, :department)
        ");
        
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':last_name', $last_name);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':department', $department);
        
        return $stmt->execute();
    } catch(PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        return false;
    }
}

// Function to check if username already exists
function username_exists($conn, $username) {
    try {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return true; // Assume username exists on error to prevent duplicate registration
    }
}

// Function to check if email already exists
function email_exists($conn, $email) {
    try {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return true; // Assume email exists on error to prevent duplicate registration
    }
}

// Function to redirect users based on their role
function redirect_by_role() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit;
    }
    
    switch ($_SESSION['user_role']) {
        case 'intern':
            header("Location: dashboards/intern_dashboard.php");
            break;
        case 'supervisor':
            header("Location: dashboards/supervisor_dashboard.php");
            break;
        case 'admin':
            header("Location: dashboards/admin_dashboard.php");
            break;
        default:
            header("Location: index.php");
            break;
    }
    exit;
}

// Function to require login for protected pages
function require_login() {
    if (!is_logged_in()) {
        $_SESSION['error'] = "You must be logged in to access this page.";
        header("Location: ../login.php");
        exit;
    }
}

// Function to require specific role for protected pages
function require_role($required_roles) {
    require_login();
    
    if (!is_array($required_roles)) {
        $required_roles = [$required_roles];
    }
    
    if (!in_array($_SESSION['user_role'], $required_roles)) {
        $_SESSION['error'] = "You do not have permission to access this page.";
        header("Location: ../index.php");
        exit;
    }
}

// Function to log out user
function logout_user() {
    // Start the session if it hasn't been started yet
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Unset all session variables
    $_SESSION = [];
    
    // If a session cookie is used, clear it
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
}
?>
