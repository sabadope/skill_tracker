<?php
include 'db_connect.php';
include '../includes/header.php'; // Include the common header

// Get the current week's logs
$current_week_start = date('Y-m-d', strtotime('monday this week'));
$current_week_end = date('Y-m-d', strtotime('sunday this week'));

// Updated queries to include feedback information
$weekly_logs = $conn->query("
    SELECT l.*, lf.feedback, lf.rating, lf.created_at as feedback_date, 
           u.first_name as supervisor_first_name, u.last_name as supervisor_last_name
    FROM logs l 
    LEFT JOIN log_feedback lf ON l.id = lf.log_id
    LEFT JOIN users u ON lf.supervisor_id = u.id
    WHERE l.type = 'Weekly Log' 
    AND DATE(l.timestamp) BETWEEN '$current_week_start' AND '$current_week_end'
    ORDER BY l.timestamp DESC
");

$daily_logs = $conn->query("
    SELECT l.*, lf.feedback, lf.rating, lf.created_at as feedback_date,
           u.first_name as supervisor_first_name, u.last_name as supervisor_last_name
    FROM logs l 
    LEFT JOIN log_feedback lf ON l.id = lf.log_id
    LEFT JOIN users u ON lf.supervisor_id = u.id
    WHERE l.type = 'Daily Log' 
    AND DATE(l.timestamp) BETWEEN '$current_week_start' AND '$current_week_end'
    ORDER BY l.timestamp DESC
");
?>

<link rel="stylesheet" href="styles.css"> <!-- Link to the new CSS file -->

<div class="container mx-auto px-4 py-8">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 border border-gray-200 dark:border-gray-700 transition-colors duration-300">
        <div class="mb-6 text-center">
            <h1 class="text-3xl font-extrabold text-gray-800 dark:text-white transition-colors duration-300">Weekly Summary</h1>
            <p class="text-lg text-gray-600 dark:text-gray-400 mt-2 transition-colors duration-300">
                For the week of <?= date('F j, Y', strtotime($current_week_start)) ?> - <?= date('F j, Y', strtotime($current_week_end)) ?></p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Weekly Log Section -->
            <div>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-4 pb-2 border-b border-gray-200 dark:border-gray-600 transition-colors duration-300">
                    <i class="fas fa-calendar-week text-blue-600 dark:text-blue-400 mr-2"></i> Weekly Log
                </h2>
                <?php if ($weekly_logs->num_rows > 0): ?>
                    <?php while ($row = $weekly_logs->fetch_assoc()): ?>
                        <div class="log-entry mb-6 p-5 border border-gray-200 dark:border-gray-600 rounded-lg shadow-sm <?php echo $row['has_feedback'] ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-700' : 'bg-white dark:bg-gray-800'; ?> transition-colors duration-300">
                            <h3 class="text-xl font-semibold text-gray-800 dark:text-white mb-3 transition-colors duration-300">
                                Weekly Summary - <?= date('M j, Y', strtotime($row['timestamp'])) ?>
                            </h3>
                            <div class="space-y-2 text-gray-700 dark:text-gray-300 text-sm transition-colors duration-300">
                            <p><strong>Goals:</strong> <?= htmlspecialchars($row['weekly_goals']) ?></p>
                            <p><strong>Achievements:</strong> <?= htmlspecialchars($row['achievements']) ?></p>
                            <p><strong>Challenges:</strong> <?= htmlspecialchars($row['challenges']) ?></p>
                            <p><strong>Lessons Learned:</strong> <?= htmlspecialchars($row['lessons']) ?></p>
                            </div>

                            <?php if ($row['has_feedback']): ?>
                                <div class="mt-4 p-3 bg-gray-100 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 transition-colors duration-300">
                                    <h4 class="font-semibold text-gray-800 dark:text-white mb-2 transition-colors duration-300">Supervisor Feedback</h4>
                                    <p class="text-gray-700 dark:text-gray-300 text-sm italic mb-2 transition-colors duration-300">"<?= htmlspecialchars($row['feedback']) ?>"</p>
                                    <div class="flex items-center text-sm mb-1">
                                        <span class="text-gray-600 dark:text-gray-400 mr-2 transition-colors duration-300">Rating:</span>
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium <?php 
                                            echo $row['rating'] === 'Excellent' ? 'bg-green-200 dark:bg-green-900 text-green-800 dark:text-green-200' : 
                                                ($row['rating'] === 'Good' ? 'bg-blue-200 dark:bg-blue-900 text-blue-800 dark:text-blue-200' : 
                                                ($row['rating'] === 'Average' ? 'bg-yellow-200 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200' : 'bg-red-200 dark:bg-red-900 text-red-800 dark:text-red-200')); 
                                        ?> transition-colors duration-300">
                                            <?= htmlspecialchars($row['rating']) ?>
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 transition-colors duration-300">
                                        By: <?= htmlspecialchars($row['supervisor_first_name'] . ' ' . $row['supervisor_last_name']) ?> on <?= date('M j, Y g:i A', strtotime($row['feedback_date'])) ?>
                                    </p>
                                </div>
                            <?php else: ?>
                                <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-700 text-sm text-yellow-800 dark:text-yellow-200 flex items-center transition-colors duration-300">
                                    <i class="fas fa-hourglass-half mr-2"></i> Awaiting supervisor feedback
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center py-10 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 transition-colors duration-300">
                        <i class="fas fa-clipboard-list text-gray-400 dark:text-gray-500 text-5xl mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white transition-colors duration-300">No weekly log found</h3>
                        <p class="text-gray-600 dark:text-gray-400 transition-colors duration-300">Add a weekly log to see your summary here.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Daily Logs Section -->
            <div>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-4 pb-2 border-b border-gray-200 dark:border-gray-600 transition-colors duration-300">
                    <i class="fas fa-calendar-day text-blue-600 dark:text-blue-400 mr-2"></i> Daily Logs for the Week
                </h2>
                <?php if ($daily_logs->num_rows > 0): ?>
                    <?php while ($row = $daily_logs->fetch_assoc()): ?>
                        <div class="log-entry mb-6 p-5 border border-gray-200 dark:border-gray-600 rounded-lg shadow-sm <?php echo $row['has_feedback'] ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-700' : 'bg-white dark:bg-gray-800'; ?> transition-colors duration-300">
                            <h3 class="text-xl font-semibold text-gray-800 dark:text-white mb-3 transition-colors duration-300">
                                Daily Log - <?= date('M j, Y', strtotime($row['timestamp'])) ?>
                            </h3>
                            <div class="space-y-2 text-gray-700 dark:text-gray-300 text-sm transition-colors duration-300">
                            <p><strong>Task:</strong> <?= htmlspecialchars($row['task_name']) ?></p>
                            <p><strong>Description:</strong> <?= htmlspecialchars($row['task_desc']) ?></p>
                            <p><strong>Time:</strong> <?= date('g:i A', strtotime($row['start_time'])) ?> - <?= date('g:i A', strtotime($row['end_time'])) ?></p>
                                <p><strong>Status:</strong> 
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium <?php 
                                        echo $row['status'] === 'Completed' ? 'bg-green-200 dark:bg-green-900 text-green-800 dark:text-green-200' : 
                                            ($row['status'] === 'In Progress' ? 'bg-yellow-200 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200' : 'bg-red-200 dark:bg-red-900 text-red-800 dark:text-red-200'); 
                                    ?> transition-colors duration-300">
                                        <?= htmlspecialchars($row['status']) ?>
                                    </span>
                                </p>
                            </div>

                            <?php if ($row['has_feedback']): ?>
                                <div class="mt-4 p-3 bg-gray-100 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 transition-colors duration-300">
                                    <h4 class="font-semibold text-gray-800 dark:text-white mb-2 transition-colors duration-300">Supervisor Feedback</h4>
                                    <p class="text-gray-700 dark:text-gray-300 text-sm italic mb-2 transition-colors duration-300">"<?= htmlspecialchars($row['feedback']) ?>"</p>
                                    <div class="flex items-center text-sm mb-1">
                                        <span class="text-gray-600 dark:text-gray-400 mr-2 transition-colors duration-300">Rating:</span>
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium <?php 
                                            echo $row['rating'] === 'Excellent' ? 'bg-green-200 dark:bg-green-900 text-green-800 dark:text-green-200' : 
                                                ($row['rating'] === 'Good' ? 'bg-blue-200 dark:bg-blue-900 text-blue-800 dark:text-blue-200' : 
                                                ($row['rating'] === 'Average' ? 'bg-yellow-200 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200' : 'bg-red-200 dark:bg-red-900 text-red-800 dark:text-red-200')); 
                                        ?> transition-colors duration-300">
                                            <?= htmlspecialchars($row['rating']) ?>
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 transition-colors duration-300">
                                        By: <?= htmlspecialchars($row['supervisor_first_name'] . ' ' . $row['supervisor_last_name']) ?> on <?= date('M j, Y g:i A', strtotime($row['feedback_date'])) ?>
                                    </p>
                                </div>
                            <?php else: ?>
                                <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-700 text-sm text-yellow-800 dark:text-yellow-200 flex items-center transition-colors duration-300">
                                    <i class="fas fa-hourglass-half mr-2"></i> Awaiting supervisor feedback
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center py-10 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 transition-colors duration-300">
                        <i class="fas fa-clipboard-list text-gray-400 dark:text-gray-500 text-5xl mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white transition-colors duration-300">No daily logs found</h3>
                        <p class="text-gray-600 dark:text-gray-400 transition-colors duration-300">Add daily logs to see them summarized here.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-8 flex justify-center space-x-4">
            <a href="index.php" class="inline-flex items-center px-6 py-3 bg-blue-600 dark:bg-blue-500 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 dark:hover:bg-blue-600 transition-colors duration-300">
                <i class="fas fa-arrow-left mr-3"></i> Back to Log Entry
            </a>
            <a href="export_logs.php?type=weekly" class="inline-flex items-center px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-800 dark:text-gray-200 font-semibold shadow-md hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-300">
                <i class="fas fa-file-export mr-3"></i> Export Weekly Summary
            </a>
        </div>
    </div>
</div>

<?php $conn->close(); ?> 