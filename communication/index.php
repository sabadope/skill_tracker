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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .message-card {
            border-left: 4px solid #0d6efd;
            margin-bottom: 1rem;
        }
        .feedback-card {
            border-left: 4px solid #198754;
            margin-bottom: 1rem;
        }
        .unread {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <?php include "../includes/header.php"; ?>

    <div class="container py-4">
        <div class="row">
            <!-- Messages Section -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Messages</h5>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                            <i class="bi bi-plus-lg"></i> New Message
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($messages)): ?>
                            <p class="text-muted">No messages yet.</p>
                        <?php else: ?>
                            <?php foreach ($messages as $message): ?>
                                <div class="card message-card <?php echo !$message['is_read'] && $message['receiver_id'] == $user_id ? 'unread' : ''; ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="card-subtitle mb-2 text-muted">
                                                From: <?php echo htmlspecialchars($message['sender_first_name'] . ' ' . $message['sender_last_name']); ?>
                                            </h6>
                                            <small class="text-muted">
                                                <?php echo date('M d, Y H:i', strtotime($message['created_at'])); ?>
                                            </small>
                                        </div>
                                        <p class="card-text"><?php echo htmlspecialchars($message['message']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Feedback Section -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Feedback</h5>
                        <?php if ($user_role === 'supervisor'): ?>
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#newFeedbackModal">
                                <i class="bi bi-plus-lg"></i> New Feedback
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($feedback)): ?>
                            <p class="text-muted">No feedback yet.</p>
                        <?php else: ?>
                            <?php foreach ($feedback as $item): ?>
                                <div class="card feedback-card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="card-subtitle mb-2 text-muted">
                                                <?php echo htmlspecialchars($item['feedback_type']); ?> Feedback
                                            </h6>
                                            <small class="text-muted">
                                                <?php echo date('M d, Y', strtotime($item['created_at'])); ?>
                                            </small>
                                        </div>
                                        <p class="card-text"><?php echo htmlspecialchars($item['content']); ?></p>
                                        <?php if ($item['rating']): ?>
                                            <div class="text-warning">
                                                <?php for ($i = 0; $i < $item['rating']; $i++): ?>
                                                    <i class="bi bi-star-fill"></i>
                                                <?php endfor; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Message Modal -->
    <div class="modal fade" id="newMessageModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="send_message.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">To:</label>
                            <select name="receiver_id" class="form-select" required>
                                <option value="">Select recipient</option>
                                <?php foreach ($users as $recipient): ?>
                                    <option value="<?php echo $recipient['id']; ?>">
                                        <?php echo htmlspecialchars($recipient['first_name'] . ' ' . $recipient['last_name'] . ' (' . $recipient['role'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message:</label>
                            <textarea name="message" class="form-control" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- New Feedback Modal -->
    <?php if ($user_role === 'supervisor'): ?>
    <div class="modal fade" id="newFeedbackModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Feedback</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="send_feedback.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Intern:</label>
                            <select name="intern_id" class="form-select" required>
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
                        <div class="mb-3">
                            <label class="form-label">Feedback Type:</label>
                            <select name="feedback_type" class="form-select" required>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rating:</label>
                            <select name="rating" class="form-select" required>
                                <option value="1">1 Star</option>
                                <option value="2">2 Stars</option>
                                <option value="3">3 Stars</option>
                                <option value="4">4 Stars</option>
                                <option value="5">5 Stars</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Feedback:</label>
                            <textarea name="content" class="form-control" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success">Submit Feedback</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 