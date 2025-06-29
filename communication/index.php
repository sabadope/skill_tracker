<?php
require_once "../config/constants.php";
require_once "../config/database.php";
require_once "../includes/auth.php";
require_once "../includes/functions.php";

// Check if user is logged in
if (!is_logged_in()) {
    header("Location: ../login.php");
    exit();
}

// Check if admin is in view mode
$is_admin_view_mode = is_admin_in_view_mode();
$admin_view_mode = get_admin_view_mode();
$selected_user_id = get_admin_selected_user_id();

// Get database connection
$database = new Database();
$conn = $database->getConnection();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Get user information
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bindParam(1, $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get messages
$stmt = $conn->prepare("
    SELECT m.*, 
           s.first_name as sender_first_name, 
           s.last_name as sender_last_name,
           r.first_name as receiver_first_name, 
           r.last_name as receiver_last_name,
           ur.role as receiver_role
    FROM messages m
    JOIN users s ON m.sender_id = s.id
    JOIN users r ON m.receiver_id = r.id
    JOIN users ur ON m.receiver_id = ur.id
    WHERE m.sender_id = :user_id OR m.receiver_id = :user_id
    ORDER BY m.created_at DESC
");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get feedback
$stmt = $conn->prepare("
    SELECT f.*, 
           i.first_name as intern_first_name, 
           i.last_name as intern_last_name,
           s.first_name as supervisor_first_name, 
           s.last_name as supervisor_last_name,
           t.title as task_title,
           t.status as task_status
    FROM feedback f
    JOIN users i ON f.intern_id = i.id
    JOIN users s ON f.supervisor_id = s.id
    JOIN mentoring_tasks t ON f.task_id = t.id
    WHERE f.intern_id = :user_id OR f.supervisor_id = :user_id
    ORDER BY f.created_at DESC
");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get users for message recipients
$stmt = $conn->prepare("SELECT id, first_name, last_name, role FROM users WHERE id != :user_id");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Communication Center - Intern Performance Monitoring System</title>
    <!-- Tailwind CSS from CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 dark:from-gray-900 dark:to-gray-800 min-h-screen transition-colors duration-200">
    <?php include "../includes/header.php"; ?>

    <div class="min-h-screen py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <?php include_once "../includes/admin_view_banner.php"; ?>

            <div class="text-center mb-12">
                <h1 class="text-4xl font-extrabold text-gray-900 dark:text-white mb-3 transition-colors duration-200">
                    <?php if ($is_admin_view_mode): ?>
                        Communication Center (Read-Only View)
                    <?php else: ?>
                        Communication Center
                    <?php endif; ?>
                </h1>
                <p class="text-lg text-gray-600 dark:text-gray-300 transition-colors duration-200">Stay connected with your team</p>
            </div>

            <div class="grid grid-cols-1 <?php echo $user_role === 'supervisor' ? 'lg:grid-cols-2' : ($user_role === 'intern' ? 'lg:grid-cols-2' : 'lg:grid-cols-1 max-w-4xl mx-auto'); ?> gap-8">
                <!-- Messages Section -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl overflow-hidden transition-colors duration-200">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-500 to-blue-600">
                        <div class="flex justify-between items-center">
                            <h5 class="text-xl font-semibold text-white flex items-center">
                                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8s-9-3.582-9-8a9 9 0 1118 0z" />
                                </svg>
                            Messages
                        </h5>
                            <button type="button" class="bg-white text-blue-600 hover:bg-blue-50 dark:bg-gray-700 dark:text-blue-400 dark:hover:bg-gray-600 px-4 py-2 rounded-xl text-sm font-semibold shadow-md transition duration-150 transform hover:scale-105" onclick="document.getElementById('newMessageModal').classList.remove('hidden')">
                                <i class="fas fa-plus mr-2"></i>New Message
                        </button>
                        </div>
                    </div>
                    <div class="p-6 bg-gray-50 dark:bg-gray-700 min-h-[500px] transition-colors duration-200">
                        <?php if (empty($messages)): ?>
                            <div class="text-center py-12">
                                <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-500 mb-4 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                <p class="text-gray-500 dark:text-gray-400 text-lg transition-colors duration-200">No messages yet</p>
                                <p class="text-gray-400 dark:text-gray-500 text-sm mt-2 transition-colors duration-200">Start a conversation with your team</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4 <?php echo $user_role !== 'supervisor' ? 'max-w-3xl mx-auto' : ''; ?>">
                            <?php foreach ($messages as $message): ?>
                                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-600 hover:shadow-md transition duration-150 <?php echo !$message['is_read'] && $message['receiver_id'] == $user_id ? 'border-l-4 border-l-blue-500' : ''; ?>">
                                        <div class="flex justify-between items-start mb-3">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center mr-3">
                                                    <span class="text-blue-600 dark:text-blue-400 font-semibold transition-colors duration-200">
                                                        <?php echo strtoupper(substr($message['sender_first_name'], 0, 1) . substr($message['sender_last_name'], 0, 1)); ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <h6 class="text-sm font-semibold text-gray-900 dark:text-white transition-colors duration-200">
                                                        <?php echo htmlspecialchars($message['sender_first_name'] . ' ' . $message['sender_last_name']); ?>
                                                        <?php if ($message['sender_id'] == $user_id): ?>
                                                            <span class="text-xs font-normal text-gray-500 dark:text-gray-400">(You)</span>
                                                        <?php endif; ?>
                                        </h6>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 transition-colors duration-200">
                                            <?php echo date('M d, Y H:i', strtotime($message['created_at'])); ?>
                                                    </p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 transition-colors duration-200">
                                                        To: <?php echo htmlspecialchars($message['receiver_first_name'] . ' ' . $message['receiver_last_name']); ?>
                                                        (<?php echo htmlspecialchars($message['receiver_role']); ?>)
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <?php if (!$message['is_read'] && $message['receiver_id'] == $user_id): ?>
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 transition-colors duration-200">New</span>
                                                <?php endif; ?>
                                                <button onclick="replyToMessage('<?php echo $message['sender_id']; ?>', '<?php echo htmlspecialchars($message['sender_first_name'] . ' ' . $message['sender_last_name']); ?>')" class="px-3 py-1 text-sm bg-blue-50 dark:bg-blue-900 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-800 transition duration-150">
                                                    <i class="fas fa-reply mr-1"></i>Reply
                                                </button>
                                                <button onclick="deleteMessage('<?php echo $message['id']; ?>')" class="px-3 py-1 text-sm bg-red-50 dark:bg-red-900 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-100 dark:hover:bg-red-800 transition duration-150">
                                                    <i class="fas fa-trash-alt mr-1"></i>Delete
                                                </button>
                                            </div>
                                        </div>
                                        <p class="text-gray-700 dark:text-gray-300 text-sm leading-relaxed transition-colors duration-200"><?php echo htmlspecialchars($message['message']); ?></p>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Feedback Section -->
                <?php if ($user_role === 'supervisor'): ?>
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl overflow-hidden transition-colors duration-200">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-green-500 to-green-600">
                        <div class="flex justify-between items-center">
                            <h5 class="text-xl font-semibold text-white flex items-center">
                                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17a2.5 2.5 0 002.5-2.5V15a2.5 2.5 0 00-5 0v.5A2.5 2.5 0 0011 17zm0 0v2m0-2a2.5 2.5 0 01-2.5-2.5V15a2.5 2.5 0 015 0v.5A2.5 2.5 0 0111 17z" />
                                </svg>
                            Give Feedback
                        </h5>
                            <button type="button" class="bg-white text-green-600 hover:bg-green-50 dark:bg-gray-700 dark:text-green-400 dark:hover:bg-gray-600 px-4 py-2 rounded-xl text-sm font-semibold shadow-md transition duration-150 transform hover:scale-105" onclick="document.getElementById('newFeedbackModal').classList.remove('hidden')">
                                <i class="fas fa-plus mr-2"></i>New Feedback
                            </button>
                        </div>
                    </div>
                    <div class="p-6 bg-gray-50 dark:bg-gray-700 min-h-[500px] transition-colors duration-200">
                        <?php if (empty($feedback)): ?>
                            <div class="text-center py-12">
                                <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-500 mb-4 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-gray-500 dark:text-gray-400 text-lg transition-colors duration-200">No feedback given yet</p>
                                <p class="text-gray-400 dark:text-gray-500 text-sm mt-2 transition-colors duration-200">Feedback you give will appear here</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                            <?php foreach ($feedback as $item): ?>
                                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-600 hover:shadow-md transition duration-150">
                                        <div class="flex justify-between items-start mb-3">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center mr-3">
                                                    <span class="text-green-600 dark:text-green-400 font-semibold transition-colors duration-200">
                                                        <?php echo strtoupper(substr($item['supervisor_first_name'], 0, 1) . substr($item['supervisor_last_name'], 0, 1)); ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <h6 class="text-sm font-semibold text-gray-900 dark:text-white transition-colors duration-200">
                                                        Task Feedback: <?php echo htmlspecialchars($item['task_title']); ?>
                                                    </h6>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 transition-colors duration-200">
                                                        <?php echo date('M d, Y', strtotime($item['created_at'])); ?>
                                                    </p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 transition-colors duration-200">
                                                        Task Status: <?php echo ucfirst(str_replace('_', ' ', $item['task_status'])); ?>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="flex items-center">
                                            <?php for ($i = 0; $i < $item['rating']; $i++): ?>
                                                    <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            <?php endfor; ?>
                                            </div>
                                        </div>
                                        <p class="text-gray-700 dark:text-gray-300 text-sm leading-relaxed mb-3 transition-colors duration-200"><?php echo htmlspecialchars($item['content']); ?></p>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 transition-colors duration-200">
                                            For: <?php echo htmlspecialchars($item['intern_first_name'] . ' ' . $item['intern_last_name']); ?>
                                        </div>
                                </div>
                            <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Feedback Section for Interns -->
                <?php if ($user_role === 'intern'): ?>
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl overflow-hidden transition-colors duration-200">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-green-500 to-green-600">
                        <div class="flex justify-between items-center">
                            <h5 class="text-xl font-semibold text-white flex items-center">
                                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            My Task Feedback
                        </h5>
                        </div>
                    </div>
                    <div class="p-6 bg-gray-50 dark:bg-gray-700 min-h-[500px] transition-colors duration-200">
                        <?php if (empty($feedback)): ?>
                            <div class="text-center py-12">
                                <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-500 mb-4 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-gray-500 dark:text-gray-400 text-lg transition-colors duration-200">No feedback received yet</p>
                                <p class="text-gray-400 dark:text-gray-500 text-sm mt-2 transition-colors duration-200">Your supervisors will provide feedback on your tasks here</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                            <?php foreach ($feedback as $item): ?>
                                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-600 hover:shadow-md transition duration-150">
                                        <div class="flex justify-between items-start mb-3">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center mr-3">
                                                    <span class="text-green-600 dark:text-green-400 font-semibold transition-colors duration-200">
                                                        <?php echo strtoupper(substr($item['supervisor_first_name'], 0, 1) . substr($item['supervisor_last_name'], 0, 1)); ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <h6 class="text-sm font-semibold text-gray-900 dark:text-white transition-colors duration-200">
                                                        Feedback on: <?php echo htmlspecialchars($item['task_title']); ?>
                                                    </h6>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 transition-colors duration-200">
                                                        From: <?php echo htmlspecialchars($item['supervisor_first_name'] . ' ' . $item['supervisor_last_name']); ?>
                                                    </p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 transition-colors duration-200">
                                                        <?php echo date('M d, Y', strtotime($item['created_at'])); ?>
                                                    </p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 transition-colors duration-200">
                                                        Task Status: <?php echo ucfirst(str_replace('_', ' ', $item['task_status'])); ?>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="flex items-center">
                                            <?php for ($i = 0; $i < $item['rating']; $i++): ?>
                                                    <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            <?php endfor; ?>
                                            </div>
                                        </div>
                                        <p class="text-gray-700 dark:text-gray-300 text-sm leading-relaxed mb-3 transition-colors duration-200"><?php echo htmlspecialchars($item['content']); ?></p>
                                    </div>
                            <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- New Message Modal -->
    <div id="newMessageModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-8 border w-full max-w-md shadow-2xl rounded-3xl bg-white dark:bg-gray-800 dark:border-gray-600 transition-colors duration-200">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors duration-200">New Message</h3>
                <button onclick="document.getElementById('newMessageModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400 transition duration-150">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form action="send_message.php" method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 transition-colors duration-200">To:</label>
                    <select name="receiver_id" id="receiver_id" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150" required>
                        <option value="">Select recipient</option>
                        <?php foreach ($users as $recipient): ?>
                            <option value="<?php echo $recipient['id']; ?>">
                                <?php echo htmlspecialchars($recipient['first_name'] . ' ' . $recipient['last_name'] . ' (' . $recipient['role'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 transition-colors duration-200">Message:</label>
                    <textarea name="message" id="message_text" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150" rows="4" required></textarea>
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="document.getElementById('newMessageModal').classList.add('hidden')" class="px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition duration-150">Cancel</button>
                    <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition duration-150 shadow-md">Send Message</button>
                </div>
            </form>
        </div>
    </div>

    <!-- New Feedback Modal -->
    <?php if ($user_role === 'supervisor'): ?>
    <div id="newFeedbackModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-8 border w-full max-w-md shadow-2xl rounded-3xl bg-white dark:bg-gray-800 dark:border-gray-600 transition-colors duration-200">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors duration-200">New Feedback</h3>
                <button onclick="document.getElementById('newFeedbackModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400 transition duration-150">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form action="send_feedback.php" method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 transition-colors duration-200">Intern:</label>
                    <select name="intern_id" id="intern_id" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition duration-150" required>
                        <option value="">Select intern</option>
                        <?php foreach ($users as $user): ?>
                            <?php if ($user['role'] === 'intern'): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 transition-colors duration-200">Select Task:</label>
                    <select name="task_id" id="task_id" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition duration-150" required>
                        <option value="">Select a task first</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 transition-colors duration-200">Rating:</label>
                    <div class="flex space-x-2">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <label class="flex-1">
                                <input type="radio" name="rating" value="<?php echo $i; ?>" class="hidden peer" required>
                                <div class="w-full py-3 text-center border border-gray-300 dark:border-gray-600 rounded-xl cursor-pointer peer-checked:bg-green-50 dark:peer-checked:bg-green-900 peer-checked:border-green-500 peer-checked:text-green-600 dark:peer-checked:text-green-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-150">
                                    <?php echo $i; ?> Star<?php echo $i > 1 ? 's' : ''; ?>
                                </div>
                            </label>
                        <?php endfor; ?>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 transition-colors duration-200">Feedback:</label>
                    <textarea name="content" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition duration-150" rows="4" required></textarea>
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="document.getElementById('newFeedbackModal').classList.add('hidden')" class="px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition duration-150">Cancel</button>
                    <button type="submit" class="px-6 py-3 bg-green-600 text-white rounded-xl hover:bg-green-700 transition duration-150 shadow-md">Send Feedback</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('newMessageModal')) {
                document.getElementById('newMessageModal').classList.add('hidden');
            }
            if (event.target == document.getElementById('newFeedbackModal')) {
                document.getElementById('newFeedbackModal').classList.add('hidden');
            }
        }

        // Function to handle reply to message
        function replyToMessage(senderId, senderName) {
            // Set the receiver in the dropdown
            const receiverSelect = document.getElementById('receiver_id');
            receiverSelect.value = senderId;
            
            // Focus the message textarea
            const messageText = document.getElementById('message_text');
            messageText.focus();
            
            // Show the modal
            document.getElementById('newMessageModal').classList.remove('hidden');
        }

        // Function to handle message deletion
        function deleteMessage(messageId) {
            if (confirm('Are you sure you want to delete this message?')) {
                window.location.href = 'delete_message.php?id=' + messageId;
            }
        }

        // Function to load tasks for selected intern
        function loadTasksForIntern(internId) {
            const taskSelect = document.getElementById('task_id');
            
            if (!internId) {
                taskSelect.innerHTML = '<option value="">Select a task first</option>';
                return;
            }

            // Show loading state
            taskSelect.innerHTML = '<option value="">Loading tasks...</option>';
            
            // Fetch tasks for the selected intern
            fetch('get_intern_tasks.php?intern_id=' + internId)
                .then(response => response.json())
                .then(data => {
                    taskSelect.innerHTML = '<option value="">Select a task</option>';
                    
                    if (data.tasks && data.tasks.length > 0) {
                        data.tasks.forEach(task => {
                            const option = document.createElement('option');
                            option.value = task.id;
                            
                            // Add status indicator to task title
                            let statusIcon = '';
                            switch (task.status) {
                                case 'pending':
                                    statusIcon = '⏳ ';
                                    break;
                                case 'in_progress':
                                    statusIcon = '🔄 ';
                                    break;
                                case 'completed':
                                    statusIcon = '✅ ';
                                    break;
                                default:
                                    statusIcon = '📋 ';
                            }
                            
                            option.textContent = statusIcon + task.title;
                            option.setAttribute('data-status', task.status);
                            taskSelect.appendChild(option);
                        });
                    } else {
                        taskSelect.innerHTML = '<option value="">No tasks found for this intern</option>';
                    }
                })
                .catch(error => {
                    console.error('Error loading tasks:', error);
                    taskSelect.innerHTML = '<option value="">Error loading tasks</option>';
                });
        }

        // Add event listener to intern selection
        document.addEventListener('DOMContentLoaded', function() {
            const internSelect = document.getElementById('intern_id');
            if (internSelect) {
                internSelect.addEventListener('change', function() {
                    loadTasksForIntern(this.value);
                });
            }
        });
    </script>
</body>
</html> 