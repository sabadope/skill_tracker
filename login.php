<?php
// login.php
require_once "config/constants.php";
require_once "config/database.php";
require_once "includes/auth.php";

// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    redirect_by_role();
    exit;
}

// Process login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get form data
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password']; // Don't sanitize password to preserve special characters
    
    // Validate form data
    $errors = [];
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    // If no errors, attempt login
    if (empty($errors)) {
        if (authenticate_user($conn, $username, $password)) {
            // Successful login, redirect to dashboard
            redirect_by_role();
            exit;
        } else {
            $errors[] = "Invalid username or password";
        }
    }
}

// Include header
require_once "includes/header.php";

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>

<div class="flex justify-center">
    <div class="w-full max-w-md">
        <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
            <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Login</h2>
            
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
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="username">
                        Username
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                           id="username" type="text" name="username" placeholder="Username" 
                           value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                        Password
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline" 
                           id="password" type="password" name="password" placeholder="********">
                </div>
                
                <div class="flex items-center justify-between">
                    <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" 
                            type="submit">
                        Sign In
                    </button>
                </div>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-gray-600">Don't have an account? Contact your administrator</p>
            </div>
        </div>
    </div>
</div>
<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
<?php
require_once "includes/footer.php";
?>
