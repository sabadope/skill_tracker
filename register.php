<?php
// User registration page (for admin use)
require_once "config/database.php";
require_once "includes/auth.php";
require_once "includes/functions.php";

// Check if user is logged in and has permission to add users
if (!is_logged_in() || 
    (
        // Only admins can add supervisors
        (isset($_GET['role']) && $_GET['role'] === 'supervisor' && !has_role('admin') && !(isset($_SESSION['can_access_supervisor']) && $_SESSION['can_access_supervisor'])) ||
        // Only admins or supervisors with permission can add interns
        (isset($_GET['role']) && $_GET['role'] === 'intern' && !has_role(['admin', 'supervisor']) && !(isset($_SESSION['can_access_supervisor']) && $_SESSION['can_access_supervisor'])) ||
        // Only admins can add admins
        (isset($_GET['role']) && $_GET['role'] === 'admin' && !has_role('admin'))
    )
) {
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
            
            // Redirect based on user role
            if ($_SESSION['user_role'] === 'supervisor') {
                header("Location: dashboards/supervisor_dashboard.php");
            } else {
            header("Location: dashboards/admin_dashboard.php");
            }
            exit;
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}

// Fetch all departments for the dropdown
$database = new Database();
$conn = $database->getConnection();
$departments = get_all_departments($conn);

// Include header
require_once "includes/header.php";
?>

<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-gray-900 dark:to-gray-800 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 transition-colors duration-300">
    <div class="w-full max-w-2xl">
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl p-10 border border-gray-200 dark:border-gray-700 transition-colors duration-300">
            <div class="text-center mb-10">
                <h2 class="text-4xl font-extrabold text-gray-900 dark:text-white mb-2">Register New User</h2>
                <p class="text-gray-600 dark:text-gray-400">Create a new account for your team member</p>
            </div>

            <?php if (isset($errors) && !empty($errors)): ?>
                <div class="mb-8 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400 dark:border-red-500 p-4 rounded-lg" role="alert">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-red-400 dark:text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-red-800 dark:text-red-200 mb-2">Please correct the following errors:</h3>
                            <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-300 space-y-1">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="space-y-8">
                <!-- Personal Information Section -->
                <div class="bg-gray-50 dark:bg-gray-700 rounded-2xl p-6 transition-colors duration-300">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-user-circle text-blue-500 dark:text-blue-400 mr-2"></i>
                        Personal Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="first_name">First Name</label>
                            <input class="appearance-none rounded-xl border border-gray-300 dark:border-gray-600 w-full py-3 px-4 text-gray-700 dark:text-white bg-white dark:bg-gray-600 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 transition duration-150" id="first_name" type="text" name="first_name" placeholder="Enter first name" value="<?php echo isset($first_name) ? htmlspecialchars($first_name) : ''; ?>">
                    </div>
                    <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="last_name">Last Name</label>
                            <input class="appearance-none rounded-xl border border-gray-300 dark:border-gray-600 w-full py-3 px-4 text-gray-700 dark:text-white bg-white dark:bg-gray-600 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 transition duration-150" id="last_name" type="text" name="last_name" placeholder="Enter last name" value="<?php echo isset($last_name) ? htmlspecialchars($last_name) : ''; ?>">
                        </div>
                    </div>
                </div>

                <!-- Contact Information Section -->
                <div class="bg-gray-50 dark:bg-gray-700 rounded-2xl p-6 transition-colors duration-300">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-envelope text-blue-500 dark:text-blue-400 mr-2"></i>
                        Contact Information
                    </h3>
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="email">Email Address</label>
                            <input class="appearance-none rounded-xl border border-gray-300 dark:border-gray-600 w-full py-3 px-4 text-gray-700 dark:text-white bg-white dark:bg-gray-600 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 transition duration-150" id="email" type="email" name="email" placeholder="Enter email address" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="username">Username</label>
                            <input class="appearance-none rounded-xl border border-gray-300 dark:border-gray-600 w-full py-3 px-4 text-gray-700 dark:text-white bg-white dark:bg-gray-600 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 transition duration-150" id="username" type="text" name="username" placeholder="Choose a username" value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
                        </div>
                </div>
                </div>

                <!-- Security Section -->
                <div class="bg-gray-50 dark:bg-gray-700 rounded-2xl p-6 transition-colors duration-300">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-lock text-blue-500 dark:text-blue-400 mr-2"></i>
                        Security
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="password">Password</label>
                            <input class="appearance-none rounded-xl border border-gray-300 dark:border-gray-600 w-full py-3 px-4 text-gray-700 dark:text-white bg-white dark:bg-gray-600 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 transition duration-150" id="password" type="password" name="password" placeholder="Create password">
                    </div>
                    <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="confirm_password">Confirm Password</label>
                            <input class="appearance-none rounded-xl border border-gray-300 dark:border-gray-600 w-full py-3 px-4 text-gray-700 dark:text-white bg-white dark:bg-gray-600 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 transition duration-150" id="confirm_password" type="password" name="confirm_password" placeholder="Confirm password">
                        </div>
                    </div>
                </div>

                <!-- Role & Department Section -->
                <div class="bg-gray-50 dark:bg-gray-700 rounded-2xl p-6 transition-colors duration-300">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-briefcase text-blue-500 dark:text-blue-400 mr-2"></i>
                        Role & Department
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="role">Role</label>
                            <select class="appearance-none rounded-xl border border-gray-300 dark:border-gray-600 w-full py-3 px-4 text-gray-700 dark:text-white bg-white dark:bg-gray-600 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 transition duration-150" id="role" name="role" <?php if (isset($_GET['role']) && in_array($_GET['role'], ['intern','supervisor'])) echo 'disabled'; ?>>
                                <option value="" disabled>Select Role</option>
                                <option value="intern" <?php 
                                    $selectedRole = isset($_GET['role']) && in_array($_GET['role'], ['intern','supervisor']) ? $_GET['role'] : (isset($role) ? $role : '');
                                    echo ($selectedRole === 'intern') ? 'selected' : '';
                                ?>>Intern</option>
                                <option value="supervisor" <?php echo ($selectedRole === 'supervisor') ? 'selected' : ''; ?>>Supervisor</option>
                        </select>
                    </div>
                        <?php if (isset($_GET['role']) && in_array($_GET['role'], ['intern','supervisor'])): ?>
                            <input type="hidden" name="role" value="<?php echo htmlspecialchars($_GET['role']); ?>">
                        <?php endif; ?>
                    <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" for="department">Department</label>
                            <select class="appearance-none rounded-xl border border-gray-300 dark:border-gray-600 w-full py-3 px-4 text-gray-700 dark:text-white bg-white dark:bg-gray-600 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 transition duration-150" id="department" name="department">
                            <option value="" disabled selected>Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept['name']); ?>" <?php echo (isset($department) && $department === $dept['name']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-6">
                    <a href="<?php echo ($_SESSION['user_role'] === 'supervisor') ? 'dashboards/supervisor_dashboard.php' : 'dashboards/admin_dashboard.php'; ?>" class="inline-flex items-center px-6 py-3 border border-gray-300 dark:border-gray-600 shadow-sm text-base font-medium rounded-xl text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800 transition duration-150">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Dashboard
                    </a>
                    <button class="inline-flex items-center px-8 py-3 border border-transparent text-base font-medium rounded-xl text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 dark:from-blue-500 dark:to-indigo-500 dark:hover:from-blue-600 dark:hover:to-indigo-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800 shadow-lg transform hover:scale-105 transition duration-150" type="submit">
                        <i class="fas fa-user-plus mr-2"></i>
                        Register User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once "includes/footer.php";
?>
