<?php
// Script to manually create admin user

// Start with a clean output
ob_start();

// Include necessary files
require_once "config/database.php";
require_once "includes/functions.php";

// Create database connection
$database = new Database();
$conn = $database->getConnection();

// Check connection
if (!$conn) {
    die("Database connection failed. Please check your database configuration.");
}

// Define admin user details
$username = "admin";
$password = "admin123";
$email = "admin@example.com";
$first_name = "Admin";
$last_name = "User";
$role = "admin";
$department = "HR";

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    // Check if the admin user already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "Admin user already exists.<br>";
    } else {
        // Prepare the insert statement
        $stmt = $conn->prepare("
            INSERT INTO users (username, password, email, first_name, last_name, role, department)
            VALUES (:username, :password, :email, :first_name, :last_name, :role, :department)
        ");
        
        // Bind parameters
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':last_name', $last_name);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':department', $department);
        
        // Execute the query
        if ($stmt->execute()) {
            echo "Admin user created successfully!<br>";
            echo "Username: " . $username . "<br>";
            echo "Password: " . $password . "<br>";
        } else {
            echo "Error creating admin user.<br>";
        }
    }
    
    // Display all password hash for verification
    echo "<br>Password hash: " . $hashed_password . "<br>";
    
    // Test the password verification
    echo "<br>Testing password verification: ";
    if (password_verify($password, $hashed_password)) {
        echo "Password verification works correctly!<br>";
    } else {
        echo "Password verification failed!<br>";
    }
    
} catch(PDOException $e) {
    echo "Database error: " . $e->getMessage();
}

// Get the output and clean the buffer
$output = ob_get_clean();

// Display the output
echo $output;
?>