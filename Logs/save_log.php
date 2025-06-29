<?php
session_start(); // Start the session at the very beginning
include 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); // Redirect to login page if not logged in
    exit();
}

$user_id = $_SESSION['user_id']; // Get the user_id from the session

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Get and sanitize daily log data
    $task_name = isset($_POST['task_name']) ? $conn->real_escape_string($_POST['task_name']) : NULL;
    $task_desc = isset($_POST['task_desc']) ? $conn->real_escape_string($_POST['task_desc']) : NULL;
    $start_time = isset($_POST['start_time']) && $_POST['start_time'] !== '' ? $_POST['start_time'] : NULL;
    $end_time = isset($_POST['end_time']) && $_POST['end_time'] !== '' ? $_POST['end_time'] : NULL;
    $status = isset($_POST['status']) && $_POST['status'] !== '' ? $conn->real_escape_string($_POST['status']) : NULL;
    
    // Get and sanitize weekly log data
    $weekly_goals = isset($_POST['weekly_goals']) ? $conn->real_escape_string($_POST['weekly_goals']) : NULL;
    $achievements = isset($_POST['achievements']) ? $conn->real_escape_string($_POST['achievements']) : NULL;
    $challenges = isset($_POST['challenges']) ? $conn->real_escape_string($_POST['challenges']) : NULL;
    $lessons = isset($_POST['lessons']) ? $conn->real_escape_string($_POST['lessons']) : NULL;

    $daily_log_filled = !empty($task_name) || !empty($task_desc) || !empty($start_time) || !empty($end_time) || !empty($status);
    $weekly_log_filled = !empty($weekly_goals) || !empty($achievements) || !empty($challenges) || !empty($lessons);

    $success_message = "";
    $error_message = "";

    if ($daily_log_filled) {
        // Prepare and bind statement for daily log
        $stmt_daily = $conn->prepare("INSERT INTO logs (user_id, type, task_name, task_desc, start_time, end_time, status) VALUES (?, 'Daily Log', ?, ?, ?, ?, ?)");
        $stmt_daily->bind_param("isssss", $user_id, $task_name, $task_desc, $start_time, $end_time, $status);

        if ($stmt_daily->execute()) {
            $success_message .= "Daily log saved successfully. ";
        } else {
            $error_message .= "Error saving daily log: " . $stmt_daily->error . ". ";
        }
        $stmt_daily->close();
    }

    if ($weekly_log_filled) {
        // Prepare and bind statement for weekly log
        $stmt_weekly = $conn->prepare("INSERT INTO logs (user_id, type, weekly_goals, achievements, challenges, lessons) VALUES (?, 'Weekly Log', ?, ?, ?, ?)");
        $stmt_weekly->bind_param("issss", $user_id, $weekly_goals, $achievements, $challenges, $lessons);

        if ($stmt_weekly->execute()) {
            $success_message .= "Weekly log saved successfully.";
        } else {
            $error_message .= "Error saving weekly log: " . $stmt_weekly->error . ".";
        }
        $stmt_weekly->close();
    }

    // Redirect back to index.php with messages
    $redirect_url = 'index.php';
    if (!empty($success_message) || !empty($error_message)) {
        $redirect_url .= '?';
        if (!empty($success_message)) {
            $redirect_url .= 'message=' . urlencode(trim($success_message));
        }
        if (!empty($error_message)) {
            if (!empty($success_message)) $redirect_url .= '&';
            $redirect_url .= 'error=' . urlencode(trim($error_message));
        }
    }
    
    // If neither daily nor weekly fields were filled
    if (!$daily_log_filled && !$weekly_log_filled) {
         $redirect_url = 'index.php?error=' . urlencode('Please fill out at least one log section.');
    }

    header("Location: " . $redirect_url);
    exit();
}

$conn->close();
?> 