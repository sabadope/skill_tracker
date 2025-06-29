<?php
include 'db_connect.php';
include '../includes/header.php'; // Include the common header

$log_id = $_GET['id'] ?? 0;
$log = null;

if ($log_id) {
    $stmt = $conn->prepare("SELECT * FROM logs WHERE id = ?");
    $stmt->bind_param("i", $log_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $log = $result->fetch_assoc();
    $stmt->close();
}

// Handle form submission for updating log
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['log_id'])) {
    $update_id = $_POST['log_id'];
    $log_type = $_POST['log_type']; // Hidden field from form

    if ($log_type === 'Daily Log') {
        $task_name = $_POST['task_name'];
        $task_desc = $_POST['task_desc'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $status = $_POST['status'];

        $stmt = $conn->prepare("UPDATE logs SET type = ?, task_name = ?, task_desc = ?, start_time = ?, end_time = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $log_type, $task_name, $task_desc, $start_time, $end_time, $status, $update_id);
    } else {
        // Weekly Log
        $weekly_goals = $_POST['weekly_goals'];
        $achievements = $_POST['achievements'];
        $challenges = $_POST['challenges'];
        $lessons = $_POST['lessons'];

        $stmt = $conn->prepare("UPDATE logs SET type = ?, weekly_goals = ?, achievements = ?, challenges = ?, lessons = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $log_type, $weekly_goals, $achievements, $challenges, $lessons, $update_id);
    }

    if ($stmt->execute()) {
        header("Location: index.php?message=Log updated successfully!");
        exit();
    } else {
        $error = "Error updating log: " . $conn->error;
    }
    $stmt->close();
}

$conn->close();
?>

<link rel="stylesheet" href="styles.css"> <!-- Link to the new CSS file -->

<div class="container">
    <div class="header">
        <h1>Edit Log Entry</h1>
        <p class="subtitle">Modify your daily or weekly log</p>
    </div>

    <?php if (isset($error)): ?>
        <div style="color: red; margin-bottom: 1rem;"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($log):
        $is_daily = ($log['type'] === 'Daily Log');
    ?>
        <div class="card">
            <form action="edit_log.php" method="POST">
                <input type="hidden" name="log_id" value="<?= $log['id'] ?>">
                <input type="hidden" name="log_type" value="<?= htmlspecialchars($log['type']) ?>">

                <?php if ($is_daily): ?>
                    <h3>Daily Log</h3>
                    <div class="form-group">
                        <label for="task_name">Task Name:</label>
                        <input type="text" id="task_name" name="task_name" value="<?= htmlspecialchars($log['task_name']) ?>" placeholder="e.g., Developed user authentication">
                    </div>
                    <div class="form-group">
                        <label for="task_desc">Task Description:</label>
                        <textarea id="task_desc" name="task_desc" placeholder="Detailed description of the task..."><?= htmlspecialchars($log['task_desc']) ?></textarea>
                    </div>
                    <div class="time-fields">
                        <div>
                            <label for="start_time">Start Time:</label>
                            <input type="time" id="start_time" name="start_time" value="<?= htmlspecialchars($log['start_time']) ?>">
                        </div>
                        <div>
                            <label for="end_time">End Time:</label>
                            <input type="time" id="end_time" name="end_time" value="<?= htmlspecialchars($log['end_time']) ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="status">Status:</label>
                        <select id="status" name="status">
                            <option value="Completed" <?= ($log['status'] === 'Completed') ? 'selected' : '' ?>>Completed</option>
                            <option value="In Progress" <?= ($log['status'] === 'In Progress') ? 'selected' : '' ?>>In Progress</option>
                            <option value="Pending" <?= ($log['status'] === 'Pending') ? 'selected' : '' ?>>Pending</option>
                        </select>
                    </div>
                <?php else: ?>
                    <h3>Weekly Log Summary</h3>
                    <div class="form-group">
                        <label for="weekly_goals">Weekly Goals Achieved:</label>
                        <textarea id="weekly_goals" name="weekly_goals" placeholder="List your weekly goals and achievements..."><?= htmlspecialchars($log['weekly_goals']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="achievements">Key Achievements:</label>
                        <textarea id="achievements" name="achievements" placeholder="What significant things did you accomplish this week?"><?= htmlspecialchars($log['achievements']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="challenges">Challenges Faced:</label>
                        <textarea id="challenges" name="challenges" placeholder="Describe any obstacles and how you addressed them..."><?= htmlspecialchars($log['challenges']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="lessons">Lessons Learned:</label>
                        <textarea id="lessons" name="lessons" placeholder="What new insights or skills did you gain?"><?= htmlspecialchars($log['lessons']) ?></textarea>
                    </div>
                <?php endif; ?>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Log</button>
                    <a href="index.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Cancel</a>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-exclamation-circle"></i>
            <h3>Log not found</h3>
            <p>The log entry you are trying to edit does not exist.</p>
            <a href="index.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back to Logs</a>
        </div>
    <?php endif; ?>
</div>
</main> 