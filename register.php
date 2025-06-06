<?php
// User registration page (for admin use)
require_once "config/database.php";
require_once "includes/auth.php";
require_once "includes/functions.php";

// Check if user is logged in and is an admin
if (!is_logged_in() || !has_role('admin')) {
    $_SESSION['error'] = "You do not have permission to access this page.";
    header("Location: login.php");
    exit;
}

// Process registration form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get form data
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password']; // Don't sanitize password to preserve special characters
    $confirm_password = $_POST['confirm_password'];
    $email = sanitize_input($_POST['email']);
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $role = sanitize_input($_POST['role']);
    $department = sanitize_input($_POST['department']);
    
    // Validate form data
    $errors = [];
    if (empty($username)) {
        $errors[] = "Username is required";
    } else if (username_exists($conn, $username)) {
        $errors[] = "Username already exists";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } else if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } else if (email_exists($conn, $email)) {
        $errors[] = "Email already exists";
    }
    
    if (empty($first_name)) {
        $errors[] = "First name is required";
    }
    
    if (empty($last_name)) {
        $errors[] = "Last name is required";
    }
    
    if (empty($role)) {
        $errors[] = "Role is required";
    }
    
    if (empty($department)) {
        $errors[] = "Department is required";
    }
    
    // If no errors, register the user
    if (empty($errors)) {
        if (register_user($conn, $username, $password, $email, $first_name, $last_name, $role, $department)) {
            $_SESSION['success'] = "User registered successfully";
            header("Location: dashboards/admin_dashboard.php");
            exit;
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}

// Include header
require_once "includes/header.php";
?>

<div class="flex justify-center">
    <div class="w-full max-w-lg">
        <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
            <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Register New User</h2>
            
            <?php if (isset($errors) && !empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <ul class="list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="mb-4 flex flex-wrap -mx-3">
                    <div class="w-full md:w-1/2 px-3 mb-6 md:mb-0">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="first_name">
                            First Name
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                               id="first_name" type="text" name="first_name" placeholder="First Name" 
                               value="<?php echo isset($first_name) ? htmlspecialchars($first_name) : ''; ?>">
                    </div>
                    <div class="w-full md:w-1/2 px-3">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="last_name">
                            Last Name
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                               id="last_name" type="text" name="last_name" placeholder="Last Name" 
                               value="<?php echo isset($last_name) ? htmlspecialchars($last_name) : ''; ?>">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                        Email
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                           id="email" type="email" name="email" placeholder="Email" 
                           value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="username">
                        Username
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                           id="username" type="text" name="username" placeholder="Username" 
                           value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
                </div>
                
                <div class="mb-4 flex flex-wrap -mx-3">
                    <div class="w-full md:w-1/2 px-3 mb-6 md:mb-0">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                            Password
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                               id="password" type="password" name="password" placeholder="********">
                    </div>
                    <div class="w-full md:w-1/2 px-3">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="confirm_password">
                            Confirm Password
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                               id="confirm_password" type="password" name="confirm_password" placeholder="********">
                    </div>
                </div>
                
                <div class="mb-4 flex flex-wrap -mx-3">
                    <div class="w-full md:w-1/2 px-3 mb-6 md:mb-0">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="role">
                            Role
                        </label>
                        <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                id="role" name="role">
                            <option value="" disabled selected>Select Role</option>
                            <option value="intern" <?php echo (isset($role) && $role === 'intern') ? 'selected' : ''; ?>>Intern</option>
                            <option value="supervisor" <?php echo (isset($role) && $role === 'supervisor') ? 'selected' : ''; ?>>Supervisor</option>
                            <option value="admin" <?php echo (isset($role) && $role === 'admin') ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    <div class="w-full md:w-1/2 px-3">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="department">
                            Department
                        </label>
                        <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                id="department" name="department">
                            <option value="" disabled selected>Select Department</option>
                            <option value="IT" <?php echo (isset($department) && $department === 'IT') ? 'selected' : ''; ?>>IT</option>
                            <option value="HR" <?php echo (isset($department) && $department === 'HR') ? 'selected' : ''; ?>>HR</option>
                            <option value="Finance" <?php echo (isset($department) && $department === 'Finance') ? 'selected' : ''; ?>>Finance</option>
                            <option value="Marketing" <?php echo (isset($department) && $department === 'Marketing') ? 'selected' : ''; ?>>Marketing</option>
                            <option value="Operations" <?php echo (isset($department) && $department === 'Operations') ? 'selected' : ''; ?>>Operations</option>
                            <option value="Sales" <?php echo (isset($department) && $department === 'Sales') ? 'selected' : ''; ?>>Sales</option>
                            <option value="Customer Support" <?php echo (isset($department) && $department === 'Customer Support') ? 'selected' : ''; ?>>Customer Support</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex items-center justify-between mt-6">
                    <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" 
                            type="submit">
                        Register User
                    </button>
                    <a class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800" href="dashboards/admin_dashboard.php">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once "includes/footer.php";
?>
