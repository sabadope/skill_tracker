<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// Get user role
$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                if ($user_role === 'supervisor' || $user_role === 'admin') {
                    $intern_id = $_POST['intern_id'];
                    $date = $_POST['date'];
                    $check_in = $_POST['check_in'];
                    $check_out = $_POST['check_out'];
                    $status = $_POST['status'];
                    
                    $sql = "INSERT INTO attendance (intern_id, date, check_in, check_out, status) 
                            VALUES (?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("issss", $intern_id, $date, $check_in, $check_out, $status);
                    
                    if ($stmt->execute()) {
                        $_SESSION['success'] = "Attendance record added successfully!";
                    } else {
                        $_SESSION['error'] = "Error adding attendance record.";
                    }
                }
                break;
                
            case 'edit':
                if ($user_role === 'supervisor' || $user_role === 'admin') {
                    $attendance_id = $_POST['attendance_id'];
                    $check_in = $_POST['check_in'];
                    $check_out = $_POST['check_out'];
                    $status = $_POST['status'];
                    
                    $sql = "UPDATE attendance SET check_in = ?, check_out = ?, status = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sssi", $check_in, $check_out, $status, $attendance_id);
                    
                    if ($stmt->execute()) {
                        $_SESSION['success'] = "Attendance record updated successfully!";
                    } else {
                        $_SESSION['error'] = "Error updating attendance record.";
                    }
                }
                break;
                
            case 'delete':
                if ($user_role === 'supervisor' || $user_role === 'admin') {
                    $attendance_id = $_POST['attendance_id'];
                    
                    $sql = "DELETE FROM attendance WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $attendance_id);
                    
                    if ($stmt->execute()) {
                        $_SESSION['success'] = "Attendance record deleted successfully!";
                    } else {
                        $_SESSION['error'] = "Error deleting attendance record.";
                    }
                }
                break;
        }
        
        // Redirect to prevent form resubmission
        header('Location: index.php');
        exit();
    }
}

// Get attendance records
if ($user_role === 'supervisor' || $user_role === 'admin') {
    $sql = "SELECT a.*, u.first_name, u.last_name, u.id as intern_id 
            FROM attendance a 
            JOIN users u ON a.intern_id = u.id 
            ORDER BY a.date DESC, u.first_name, u.last_name";
    $result = $conn->query($sql);
} else {
    // For interns, only show their own records
    $sql = "SELECT a.*, u.id as intern_id FROM attendance a 
            JOIN users u ON a.intern_id = u.id 
            WHERE a.intern_id = ? 
            ORDER BY a.date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
}

// Include header after all redirects and processing
require_once '../includes/header.php';
?>

<div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 dark:from-gray-900 dark:to-gray-800 py-12 transition-colors duration-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-extrabold text-gray-900 dark:text-white mb-3 transition-colors duration-200">Attendance Management</h1>
            <p class="text-lg text-gray-600 dark:text-gray-300 transition-colors duration-200">Track and manage attendance records</p>
        </div>

        <?php if ($user_role === 'supervisor' || $user_role === 'admin'): ?>
        <!-- Add Attendance Form -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8 mb-8 transition-colors duration-200">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors duration-200">Add Attendance Record</h2>
                <button type="button" onclick="toggleAddForm()" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors duration-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 13l-7 7-7-7m14-8l-7 7-7-7" />
                    </svg>
                </button>
            </div>
            <form action="" method="POST" class="space-y-6" id="addAttendanceForm">
                <input type="hidden" name="action" value="add">
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 transition-colors duration-200">Intern</label>
                        <select name="intern_id" required class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150">
                            <?php
                            $interns = $conn->query("SELECT id, first_name, last_name FROM users WHERE role = 'intern' ORDER BY first_name, last_name");
                            while ($intern = $interns->fetch_assoc()) {
                                echo "<option value='{$intern['id']}'>{$intern['first_name']} {$intern['last_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 transition-colors duration-200">Date</label>
                        <input type="date" name="date" required class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 transition-colors duration-200">Check In</label>
                        <input type="time" name="check_in" required class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 transition-colors duration-200">Check Out</label>
                        <input type="time" name="check_out" required class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 transition-colors duration-200">Status</label>
                        <select name="status" required class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150">
                            <option value="present">Present</option>
                            <option value="late">Late</option>
                            <option value="absent">Absent</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition duration-150 shadow-md">
                        Add Record
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- Attendance Records Table -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl overflow-hidden transition-colors duration-200">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-500 to-blue-600">
                <h2 class="text-xl font-semibold text-white">Attendance Records</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider transition-colors duration-200">Intern ID</th>
                            <?php if ($user_role === 'supervisor' || $user_role === 'admin'): ?>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider transition-colors duration-200">Intern Name</th>
                            <?php endif; ?>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider transition-colors duration-200">Date</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider transition-colors duration-200">Check In</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider transition-colors duration-200">Check Out</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider transition-colors duration-200">Status</th>
                            <?php if ($user_role === 'supervisor' || $user_role === 'admin'): ?>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider transition-colors duration-200">Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-150">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 transition-colors duration-200">
                                <?php echo htmlspecialchars($row['intern_id']); ?>
                            </td>
                            <?php if ($user_role === 'supervisor' || $user_role === 'admin'): ?>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center mr-3">
                                        <span class="text-blue-600 dark:text-blue-400 font-semibold transition-colors duration-200">
                                            <?php echo strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1)); ?>
                                        </span>
                                    </div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white transition-colors duration-200">
                                        <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                                    </div>
                                </div>
                            </td>
                            <?php endif; ?>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 transition-colors duration-200">
                                <?php echo date('F d, Y', strtotime($row['date'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 transition-colors duration-200">
                                <?php echo date('h:i A', strtotime($row['check_in'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 transition-colors duration-200">
                                <?php echo date('h:i A', strtotime($row['check_out'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php
                                    switch($row['status']) {
                                        case 'present':
                                            echo 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
                                            break;
                                        case 'late':
                                            echo 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
                                            break;
                                        case 'absent':
                                            echo 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
                                            break;
                                    }
                                    ?> transition-colors duration-200">
                                    <?php echo ucfirst(htmlspecialchars($row['status'])); ?>
                                </span>
                            </td>
                            <?php if ($user_role === 'supervisor' || $user_role === 'admin'): ?>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="editAttendance(<?php echo htmlspecialchars(json_encode($row)); ?>)" 
                                        class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3 transition-colors duration-200">Edit</button>
                                <form action="" method="POST" class="inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="attendance_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 transition-colors duration-200" 
                                            onclick="return confirm('Are you sure you want to delete this record?')">Delete</button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if ($user_role === 'supervisor' || $user_role === 'admin'): ?>
<!-- Edit Attendance Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-8 border w-full max-w-md shadow-2xl rounded-3xl bg-white dark:bg-gray-800 dark:border-gray-600 transition-colors duration-200">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors duration-200">Edit Attendance Record</h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400 transition duration-150">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form action="" method="POST" class="space-y-6">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="attendance_id" id="edit_attendance_id">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 transition-colors duration-200">Check In</label>
                <input type="time" name="check_in" id="edit_check_in" required 
                       class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 transition-colors duration-200">Check Out</label>
                <input type="time" name="check_out" id="edit_check_out" required 
                       class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 transition-colors duration-200">Status</label>
                <select name="status" id="edit_status" required 
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150">
                    <option value="present">Present</option>
                    <option value="late">Late</option>
                    <option value="absent">Absent</option>
                </select>
            </div>
            
            <div class="flex justify-end space-x-4">
                <button type="button" onclick="closeEditModal()" 
                        class="px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition duration-150">Cancel</button>
                <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition duration-150 shadow-md">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleAddForm() {
    const form = document.getElementById('addAttendanceForm');
    form.classList.toggle('hidden');
}

function editAttendance(record) {
    document.getElementById('edit_attendance_id').value = record.id;
    document.getElementById('edit_check_in').value = record.check_in;
    document.getElementById('edit_check_out').value = record.check_out;
    document.getElementById('edit_status').value = record.status;
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

// Close modals when clicking outside
window.onclick = function(event) {
    if (event.target == document.getElementById('editModal')) {
        closeEditModal();
    }
}
</script>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?> 