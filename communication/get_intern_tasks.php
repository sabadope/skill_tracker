<?php
require_once "../config/constants.php";
require_once "../config/database.php";
require_once "../includes/auth.php";

// Check if user is logged in and is a supervisor
if (!is_logged_in() || $_SESSION['user_role'] !== 'supervisor') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get database connection
$database = new Database();
$conn = $database->getConnection();

// Get intern ID from request
$intern_id = isset($_GET['intern_id']) ? (int)$_GET['intern_id'] : 0;

if (!$intern_id) {
    echo json_encode(['error' => 'Intern ID is required']);
    exit();
}

try {
    // Fetch ALL tasks assigned to the selected intern (both pending and completed)
    $stmt = $conn->prepare("
        SELECT id, title, description, status, created_at, due_date
        FROM mentoring_tasks 
        WHERE intern_id = :intern_id 
        ORDER BY 
            CASE 
                WHEN status = 'pending' THEN 1
                WHEN status = 'in_progress' THEN 2
                WHEN status = 'completed' THEN 3
                ELSE 4
            END,
            created_at DESC
    ");
    $stmt->bindParam(':intern_id', $intern_id);
    $stmt->execute();
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format tasks with status indicators
    $formatted_tasks = [];
    foreach ($tasks as $task) {
        $status_text = ucfirst(str_replace('_', ' ', $task['status']));
        $status_class = '';
        
        switch ($task['status']) {
            case 'pending':
                $status_class = 'text-yellow-600';
                break;
            case 'in_progress':
                $status_class = 'text-blue-600';
                break;
            case 'completed':
                $status_class = 'text-green-600';
                break;
            default:
                $status_class = 'text-gray-600';
        }
        
        $formatted_tasks[] = [
            'id' => $task['id'],
            'title' => $task['title'] . ' (' . $status_text . ')',
            'description' => $task['description'],
            'status' => $task['status'],
            'status_text' => $status_text,
            'status_class' => $status_class,
            'created_at' => $task['created_at'],
            'due_date' => $task['due_date']
        ];
    }

    // Return tasks as JSON
    header('Content-Type: application/json');
    echo json_encode(['tasks' => $formatted_tasks]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 