<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';

$user_id = $_SESSION['user_id'];
$success = $error = '';

// Generate QR code URL for feedback (interns only)
$qr_code_url = null;
if ($_SESSION['user_role'] === 'intern') {
    $qr_code_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/feedback_form.php?user_id=" . $user_id . "&role=intern";
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = (new Database())->getConnection();

    // Clear profile picture
    if (isset($_POST['clear_pic'])) {
        // Get current profile_pic path
        $stmt = $db->prepare("SELECT profile_pic FROM users WHERE id = :id");
        $stmt->execute([':id' => $user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['profile_pic']) && file_exists($row['profile_pic'])) {
            unlink($row['profile_pic']);
        }
        $stmt = $db->prepare("UPDATE users SET profile_pic = NULL WHERE id = :id");
        $stmt->execute([':id' => $user_id]);
        $success = 'Profile picture removed!';
    }

    // Update name
    if (!empty($_POST['first_name']) || !empty($_POST['last_name'])) {
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $stmt = $db->prepare("UPDATE users SET first_name = :first_name, last_name = :last_name WHERE id = :id");
        $stmt->execute([':first_name' => $first_name, ':last_name' => $last_name, ':id' => $user_id]);
        $_SESSION['user_name'] = $first_name . ' ' . $last_name;
        $success = 'Name updated successfully!';
    }

    // Update password
    if (!empty($_POST['current_password']) && !empty($_POST['new_password'])) {
        $current = $_POST['current_password'];
        $new = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $stmt = $db->prepare("SELECT password FROM users WHERE id = :id");
        $stmt->execute([':id' => $user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && password_verify($current, $row['password'])) {
            $stmt = $db->prepare("UPDATE users SET password = :new WHERE id = :id");
            $stmt->execute([':new' => $new, ':id' => $user_id]);
            $success = 'Password updated successfully!';
        } else {
            $error = 'Current password is incorrect!';
        }
    }

    // Upload profile picture
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
        $target_dir = 'assets/profile_pics/';
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $target_file = $target_dir . 'user_' . $user_id . '.' . $ext;
        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_file)) {
            $stmt = $db->prepare("UPDATE users SET profile_pic = :pic WHERE id = :id");
            $stmt->execute([':pic' => $target_file, ':id' => $user_id]);
            $success = 'Profile picture updated!';
        } else {
            $error = 'Failed to upload image.';
        }
    }
}

// Fetch user info
$db = (new Database())->getConnection();
$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$profile_pic = !empty($user['profile_pic']) ? $user['profile_pic'] : 'assets/profile_pics/default.png';
?>
<?php include 'includes/header.php'; ?>
<div class="max-w-xl mx-auto mt-10 bg-white dark:bg-gray-800 p-8 rounded shadow">
    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Profile Settings</h2>
    <?php if ($success): ?><div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4"><?php echo $success; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="bg-red-100 text-red-800 px-4 py-2 rounded mb-4"><?php echo $error; ?></div><?php endif; ?>
    
    <form method="post" enctype="multipart/form-data" class="space-y-6">
        <?php if ($_SESSION['user_role'] === 'intern'): ?>
        <!-- QR Code View Section for Interns -->
        <div class="flex items-center gap-4">
            <div class="w-20 h-20 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center border-2 border-blue-400">
                <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                </svg>
            </div>
            <button type="button" onclick="toggleQRCode()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-200">
                View QR Code
            </button>
        </div>
        
        <!-- Hidden QR Code Modal -->
        <div id="qrCodeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-8 max-w-md mx-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Your Feedback QR Code</h3>
                    <button onclick="toggleQRCode()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="text-center">
                    <div class="bg-white p-4 rounded-lg inline-block mb-4">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=<?php echo urlencode($qr_code_url); ?>" 
                             alt="Feedback QR Code" 
                             class="w-64 h-64 mx-auto">
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                        Share this QR code with customers to get feedback about your services
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-500 break-all">
                        <?php echo htmlspecialchars($qr_code_url); ?>
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Profile Information Section -->
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Profile Information</h3>
                <div id="editButtons">
                    <button type="button" onclick="enableEdit()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition duration-200">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit
                    </button>
                </div>
            </div>
            
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 mb-1 font-medium">First Name</label>
                        <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" 
                               class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-300 dark:bg-gray-600 dark:text-white disabled:bg-gray-100 disabled:text-gray-500 dark:disabled:bg-gray-800 dark:disabled:text-gray-400" 
                               disabled />
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 mb-1 font-medium">Last Name</label>
                        <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" 
                               class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-300 dark:bg-gray-600 dark:text-white disabled:bg-gray-100 disabled:text-gray-500 dark:disabled:bg-gray-800 dark:disabled:text-gray-400" 
                               disabled />
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-700 dark:text-gray-300 mb-1 font-medium">Email</label>
                    <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                           class="w-full px-3 py-2 border rounded bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400" 
                           disabled />
                </div>
                
                <div>
                    <label class="block text-gray-700 dark:text-gray-300 mb-1 font-medium">Role</label>
                    <input type="text" value="<?php echo ucfirst(htmlspecialchars($user['role'])); ?>" 
                           class="w-full px-3 py-2 border rounded bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400" 
                           disabled />
                </div>
                
                <div>
                    <label class="block text-gray-700 dark:text-gray-300 mb-1 font-medium">Department</label>
                    <input type="text" value="<?php echo htmlspecialchars($user['department']); ?>" 
                           class="w-full px-3 py-2 border rounded bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400" 
                           disabled />
                </div>
            </div>
        </div>
        
        <!-- Password Change Section -->
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Change Password</h3>
                <div id="passwordEditButtons">
                    <button type="button" onclick="enablePasswordEdit()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded transition duration-200">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        Change Password
                    </button>
                </div>
            </div>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-gray-700 dark:text-gray-300 mb-1 font-medium">Current Password</label>
                    <input type="password" name="current_password" 
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-300 dark:bg-gray-600 dark:text-white disabled:bg-gray-100 disabled:text-gray-500 dark:disabled:bg-gray-800 dark:disabled:text-gray-400" 
                           disabled />
                </div>
                <div>
                    <label class="block text-gray-700 dark:text-gray-300 mb-1 font-medium">New Password</label>
                    <input type="password" name="new_password" 
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-300 dark:bg-gray-600 dark:text-white disabled:bg-gray-100 disabled:text-gray-500 dark:disabled:bg-gray-800 dark:disabled:text-gray-400" 
                           disabled />
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div id="actionButtons" class="hidden">
            <div class="flex gap-4">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded transition duration-200">
                    Save Changes
                </button>
                <button type="button" onclick="cancelEdit()" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded transition duration-200">
                    Cancel
                </button>
            </div>
        </div>
    </form>
    
    <script>
    function toggleQRCode() {
        const modal = document.getElementById('qrCodeModal');
        modal.classList.toggle('hidden');
    }
    
    function enableEdit() {
        // Enable name fields
        document.querySelector('input[name="first_name"]').disabled = false;
        document.querySelector('input[name="last_name"]').disabled = false;
        document.querySelector('input[name="first_name"]').focus();
        
        // Show action buttons
        document.getElementById('actionButtons').classList.remove('hidden');
        
        // Hide edit button
        document.getElementById('editButtons').innerHTML = `
            <span class="text-blue-600 dark:text-blue-400 text-sm">Editing...</span>
        `;
    }
    
    function enablePasswordEdit() {
        // Enable password fields
        document.querySelector('input[name="current_password"]').disabled = false;
        document.querySelector('input[name="new_password"]').disabled = false;
        document.querySelector('input[name="current_password"]').focus();
        
        // Show action buttons
        document.getElementById('actionButtons').classList.remove('hidden');
        
        // Hide password edit button
        document.getElementById('passwordEditButtons').innerHTML = `
            <span class="text-green-600 dark:text-green-400 text-sm">Changing Password...</span>
        `;
    }
    
    function cancelEdit() {
        // Disable all fields
        document.querySelector('input[name="first_name"]').disabled = true;
        document.querySelector('input[name="last_name"]').disabled = true;
        document.querySelector('input[name="current_password"]').disabled = true;
        document.querySelector('input[name="new_password"]').disabled = true;
        
        // Clear password fields
        document.querySelector('input[name="current_password"]').value = '';
        document.querySelector('input[name="new_password"]').value = '';
        
        // Hide action buttons
        document.getElementById('actionButtons').classList.add('hidden');
        
        // Restore edit buttons
        document.getElementById('editButtons').innerHTML = `
            <button type="button" onclick="enableEdit()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition duration-200">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Edit
            </button>
        `;
        
        document.getElementById('passwordEditButtons').innerHTML = `
            <button type="button" onclick="enablePasswordEdit()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded transition duration-200">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                Change Password
            </button>
        `;
    }
    
    // Close modal when clicking outside
    document.getElementById('qrCodeModal').addEventListener('click', function(e) {
        if (e.target === this) {
            toggleQRCode();
        }
    });
    </script>
</div>
<?php include 'includes/footer.php'; ?> 