<?php
require_once "../config/constants.php";
require_once "../config/database.php";
require_once "../includes/auth.php";

// Check if user is logged in
if (!is_logged_in()) {
    header("Location: ../login.php");
    exit();
}

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
           r.last_name as receiver_last_name
    FROM messages m
    JOIN users s ON m.sender_id = s.id
    JOIN users r ON m.receiver_id = r.id
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
           s.last_name as supervisor_last_name
    FROM feedback f
    JOIN users i ON f.intern_id = i.id
    JOIN users s ON f.supervisor_id = s.id
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
<body class="bg-gray-100 min-h-screen">
    <?php include "../includes/header.php"; ?>

    <div class="container mx-auto px-4 py-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Messages Section -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                    <h5 class="text-lg font-semibold text-gray-800">Messages</h5>
                    <button type="button" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm" onclick="document.getElementById('newMessageModal').classList.remove('hidden')">
                        <i class="fas fa-plus"></i> New Message
                    </button>
                </div>
                <div class="p-4">
                    <?php if (empty($messages)): ?>
                        <p class="text-gray-500 text-center py-4">No messages yet.</p>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="border-l-4 border-blue-500 mb-4 p-4 bg-white rounded shadow-sm <?php echo !$message['is_read'] && $message['receiver_id'] == $user_id ? 'bg-gray-50' : ''; ?>">
                                <div class="flex justify-between items-start mb-2">
                                    <h6 class="text-sm text-gray-600">
                                        From: <?php echo htmlspecialchars($message['sender_first_name'] . ' ' . $message['sender_last_name']); ?>
                                    </h6>
                                    <span class="text-xs text-gray-500">
                                        <?php echo date('M d, Y H:i', strtotime($message['created_at'])); ?>
                                    </span>
                                </div>
                                <p class="text-gray-800"><?php echo htmlspecialchars($message['message']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Feedback Section -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                    <h5 class="text-lg font-semibold text-gray-800">Feedback</h5>
                    <?php if ($user_role === 'supervisor'): ?>
                        <button type="button" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm" onclick="document.getElementById('newFeedbackModal').classList.remove('hidden')">
                            <i class="fas fa-plus"></i> New Feedback
                        </button>
                    <?php endif; ?>
                </div>
                <div class="p-4">
                    <?php if (empty($feedback)): ?>
                        <p class="text-gray-500 text-center py-4">No feedback yet.</p>
                    <?php else: ?>
                        <?php foreach ($feedback as $item): ?>
                            <div class="border-l-4 border-green-500 mb-4 p-4 bg-white rounded shadow-sm">
                                <div class="flex justify-between items-start mb-2">
                                    <h6 class="text-sm text-gray-600">
                                        <?php echo htmlspecialchars($item['feedback_type']); ?> Feedback
                                    </h6>
                                    <span class="text-xs text-gray-500">
                                        <?php echo date('M d, Y', strtotime($item['created_at'])); ?>
                                    </span>
                                </div>
                                <p class="text-gray-800 mb-2"><?php echo htmlspecialchars($item['content']); ?></p>
                                <?php if ($item['rating']): ?>
                                    <div class="flex text-yellow-400">
                                        <?php for ($i = 0; $i < $item['rating']; $i++): ?>
                                            <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        <?php endfor; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- New Message Modal -->
    <div id="newMessageModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">New Message</h3>
                <button onclick="document.getElementById('newMessageModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-500">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form action="send_message.php" method="POST">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">To:</label>
                    <select name="receiver_id" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">Select recipient</option>
                        <?php foreach ($users as $recipient): ?>
                            <option value="<?php echo $recipient['id']; ?>">
                                <?php echo htmlspecialchars($recipient['first_name'] . ' ' . $recipient['last_name'] . ' (' . $recipient['role'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Message:</label>
                    <textarea name="message" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" rows="4" required></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('newMessageModal').classList.add('hidden')" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">Send Message</button>
                </div>
            </form>
        </div>
    </div>

    <!-- New Feedback Modal -->
    <?php if ($user_role === 'supervisor'): ?>
    <div id="newFeedbackModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">New Feedback</h3>
                <button onclick="document.getElementById('newFeedbackModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-500">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form action="send_feedback.php" method="POST">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Intern:</label>
                    <select name="intern_id" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
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
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Feedback Type:</label>
                    <select name="feedback_type" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Rating:</label>
                    <select name="rating" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="1">1 Star</option>
                        <option value="2">2 Stars</option>
                        <option value="3">3 Stars</option>
                        <option value="4">4 Stars</option>
                        <option value="5">5 Stars</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Feedback:</label>
                    <textarea name="content" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" rows="4" required></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('newFeedbackModal').classList.add('hidden')" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600">Send Feedback</button>
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
    </script>
</body>
</html> 