<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'db_connect.php';

// Check if user is logged in and is a supervisor
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'supervisor') {
    $_SESSION['error'] = "You must be logged in as a supervisor to provide feedback.";
    header("Location: ../index.php");
    exit();
}

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $log_id = $_POST['log_id'];
    $feedback = $_POST['feedback'];
    $rating = $_POST['rating'];
    $supervisor_id = $_SESSION['user_id'];

    // Verify supervisor exists
    $check_supervisor = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'supervisor'");
    $check_supervisor->bind_param("i", $supervisor_id);
    $check_supervisor->execute();
    $result = $check_supervisor->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error'] = "Invalid supervisor account.";
        header("Location: admin_view.php");
        exit();
    }

    // Verify log exists and hasn't been reviewed
    $check_log = $conn->prepare("SELECT id FROM logs WHERE id = ? AND has_feedback = 0");
    $check_log->bind_param("i", $log_id);
    $check_log->execute();
    $log_result = $check_log->get_result();

    if ($log_result->num_rows === 0) {
        $_SESSION['error'] = "Log not found or already reviewed.";
        header("Location: admin_view.php");
        exit();
    }

    try {
        // Start transaction
        $conn->begin_transaction();

        // Insert feedback
        $stmt = $conn->prepare("INSERT INTO log_feedback (log_id, supervisor_id, feedback, rating) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $log_id, $supervisor_id, $feedback, $rating);
        
        if (!$stmt->execute()) {
            throw new Exception("Error inserting feedback");
        }

        // Update log status
        $update_stmt = $conn->prepare("UPDATE logs SET has_feedback = 1, feedback_status = 'reviewed' WHERE id = ?");
        $update_stmt->bind_param("i", $log_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception("Error updating log status");
        }

        // Commit transaction
        $conn->commit();
        $_SESSION['success'] = "Feedback submitted successfully!";
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error'] = "Error submitting feedback: " . $e->getMessage();
    }
    
    header("Location: admin_view.php");
    exit();
}

// Fetch all logs with user information and feedback
$query = "SELECT l.*, u.first_name, u.last_name, lf.feedback, lf.rating, lf.created_at as feedback_date 
          FROM logs l 
          LEFT JOIN users u ON l.user_id = u.id 
          LEFT JOIN log_feedback lf ON l.id = lf.log_id 
          ORDER BY l.timestamp DESC";
$all_logs = $conn->query($query);

// Now include the header after all potential redirects
include '../includes/header.php';
?>

<div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 dark:from-gray-900 dark:to-gray-800 py-12 transition-colors duration-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-extrabold text-gray-900 dark:text-white mb-3 transition-colors duration-200">Skill Development Logs</h1>
            <p class="text-lg text-gray-600 dark:text-gray-300 transition-colors duration-200">Review and provide feedback on intern progress</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl overflow-hidden transition-colors duration-200">
            <?php if ($all_logs->num_rows > 0): ?>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php while ($row = $all_logs->fetch_assoc()): ?>
                    <div class="p-8 hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-150 <?php echo $row['has_feedback'] ? 'bg-blue-50 dark:bg-blue-900' : 'bg-white dark:bg-gray-800'; ?>">
                        <div class="flex justify-between items-start mb-6">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                                    <span class="text-blue-600 dark:text-blue-400 font-semibold transition-colors duration-200">
                                        <?php echo strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1)); ?>
                                    </span>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center transition-colors duration-200">
                                        <?php if ($row['type'] === 'Weekly Log'): ?>
                                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        <?php else: ?>
                                            <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        <?php endif; ?>
                                        <?= $row['type'] ?> - <?= date('M j, Y', strtotime($row['timestamp'])) ?>
                                    </h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 transition-colors duration-200">By: <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></p>
                                </div>
                            </div>
                            <div class="flex space-x-3">
                                <?php if (!$row['has_feedback']): ?>
                                    <button onclick="showFeedbackForm(<?= $row['id'] ?>)" 
                                            class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition duration-150 shadow-md flex items-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Add Feedback
                                    </button>
                                <?php else: ?>
                                    <span class="px-4 py-2 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-xl text-sm font-medium flex items-center transition-colors duration-200">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Reviewed
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($row['type'] === 'Weekly Log'): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50 dark:bg-gray-700 rounded-2xl p-6 transition-colors duration-200">
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2 transition-colors duration-200">Goals</h4>
                                <p class="text-gray-900 dark:text-white transition-colors duration-200"><?= htmlspecialchars($row['weekly_goals']) ?></p>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2 transition-colors duration-200">Achievements</h4>
                                <p class="text-gray-900 dark:text-white transition-colors duration-200"><?= htmlspecialchars($row['achievements']) ?></p>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2 transition-colors duration-200">Challenges</h4>
                                <p class="text-gray-900 dark:text-white transition-colors duration-200"><?= htmlspecialchars($row['challenges']) ?></p>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2 transition-colors duration-200">Lessons Learned</h4>
                                <p class="text-gray-900 dark:text-white transition-colors duration-200"><?= htmlspecialchars($row['lessons']) ?></p>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50 dark:bg-gray-700 rounded-2xl p-6 transition-colors duration-200">
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2 transition-colors duration-200">Task</h4>
                                <p class="text-gray-900 dark:text-white transition-colors duration-200"><?= htmlspecialchars($row['task_name']) ?></p>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2 transition-colors duration-200">Description</h4>
                                <p class="text-gray-900 dark:text-white transition-colors duration-200"><?= htmlspecialchars($row['task_desc']) ?></p>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2 transition-colors duration-200">Time</h4>
                                <p class="text-gray-900 dark:text-white transition-colors duration-200"><?= date('g:i A', strtotime($row['start_time'])) ?> - <?= date('g:i A', strtotime($row['end_time'])) ?></p>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2 transition-colors duration-200">Status</h4>
                                <span class="px-3 py-1 rounded-full text-sm font-medium <?php 
                                    echo $row['status'] === 'Completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                        ($row['status'] === 'In Progress' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'); 
                                ?> transition-colors duration-200">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($row['has_feedback']): ?>
                        <div class="mt-6 bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 transition-colors duration-200">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white transition-colors duration-200">Feedback</h4>
                                <span class="px-3 py-1 rounded-full text-sm font-medium <?php 
                                    echo $row['rating'] === 'Excellent' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                        ($row['rating'] === 'Good' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 
                                        ($row['rating'] === 'Average' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200')); 
                                ?> transition-colors duration-200">
                                    <?= htmlspecialchars($row['rating']) ?>
                                </span>
                            </div>
                            <p class="text-gray-700 dark:text-gray-300 transition-colors duration-200 mb-4"><?= htmlspecialchars($row['feedback']) ?></p>
                            <p class="text-sm text-gray-500 dark:text-gray-500 transition-colors duration-200">
                                Given on: <?= date('M j, Y g:i A', strtotime($row['feedback_date'])) ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
            <div class="text-center py-16">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="text-gray-500 text-lg">No logs found.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Feedback Modal -->
<div id="feedbackModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-8 border w-full max-w-md shadow-2xl rounded-3xl bg-white">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold text-gray-900">Add Feedback</h3>
            <button onclick="hideFeedbackForm()" class="text-gray-400 hover:text-gray-500 transition duration-150">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form id="feedbackForm" method="POST" class="space-y-6">
            <input type="hidden" name="log_id" id="log_id">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                <select name="rating" required class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150">
                    <option value="">Select Rating</option>
                    <option value="Excellent">Excellent</option>
                    <option value="Good">Good</option>
                    <option value="Average">Average</option>
                    <option value="Needs Improvement">Needs Improvement</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Feedback</label>
                <textarea name="feedback" required rows="4" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150"></textarea>
            </div>
            <div class="flex justify-end space-x-4">
                <button type="button" onclick="hideFeedbackForm()" 
                        class="px-6 py-3 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition duration-150">
                    Cancel
                </button>
                <button type="submit" name="submit_feedback" 
                        class="px-6 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition duration-150 shadow-md">
                    Submit Feedback
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showFeedbackForm(logId) {
    document.getElementById('log_id').value = logId;
    document.getElementById('feedbackModal').classList.remove('hidden');
}

function hideFeedbackForm() {
    document.getElementById('feedbackModal').classList.add('hidden');
    document.getElementById('feedbackForm').reset();
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target == document.getElementById('feedbackModal')) {
        hideFeedbackForm();
    }
}
</script>

<?php $conn->close(); ?> 