<?php
include 'db_connect.php';

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="skill_development_logs_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add CSV headers
fputcsv($output, [
    'Type',
    'Skill Name',
    'Skill Description',
    'Start Time',
    'End Time',
    'Status',
    'Weekly Goals',
    'Achievements',
    'Challenges',
    'Lessons Learned',
    'Timestamp'
]);

// Get all logs
$result = $conn->query("SELECT * FROM logs ORDER BY timestamp DESC");

// Write data rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['type'],
        $row['task_name'],
        $row['task_desc'],
        $row['start_time'],
        $row['end_time'],
        $row['status'],
        $row['weekly_goals'],
        $row['achievements'],
        $row['challenges'],
        $row['lessons'],
        $row['timestamp']
    ]);
}

// Close the output stream
fclose($output);

$conn->close();
?> 