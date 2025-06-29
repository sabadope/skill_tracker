<?php
include 'db_connect.php';
include '../includes/header.php'; // Include the common header

// Fetch saved logs (both daily and weekly)
$recent_logs = $conn->query("SELECT * FROM logs ORDER BY timestamp DESC LIMIT 5");
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="text-center mb-12">
        <h1 class="text-4xl font-extrabold text-gray-900 dark:text-white mb-3 transition-colors duration-200">Skill Development Logs</h1>
        <p class="text-lg text-gray-600 dark:text-gray-300 transition-colors duration-200">Track your daily activities and weekly progress</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Log Form -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl overflow-hidden transition-colors duration-200">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-500 to-blue-600">
                    <h2 class="text-xl font-semibold text-white flex items-center">
                        <i class="fas fa-pencil-alt mr-2"></i>
                        Log Your Activities
                    </h2>
                </div>
                <div class="p-6">
                    <?php if (isset($_GET['message'])): ?>
                        <div class="mb-4 p-4 bg-green-50 dark:bg-green-900 text-green-700 dark:text-green-300 rounded-xl transition-colors duration-200"><?php echo htmlspecialchars($_GET['message']); ?></div>
                    <?php endif; ?>
                    <?php if (isset($_GET['error'])): ?>
                        <div class="mb-4 p-4 bg-red-50 dark:bg-red-900 text-red-700 dark:text-red-300 rounded-xl transition-colors duration-200"><?php echo htmlspecialchars($_GET['error']); ?></div>
                    <?php endif; ?>

                    <form action="save_log.php" method="POST" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 transition-colors duration-200">Log Type:</label>
                            <select id="logType" onchange="toggleLogFields()" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150">
                                <option value="daily">Daily Log</option>
                                <option value="weekly">Weekly Log</option>
                            </select>
                        </div>

                        <div id="dailyLogFields" class="space-y-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white transition-colors duration-200">Daily Log</h3>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 transition-colors duration-200">Task Name:</label>
                                <input type="text" id="task_name" name="task_name" placeholder="e.g., Developed user authentication" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 transition-colors duration-200">Task Description:</label>
                                <textarea id="task_desc" name="task_desc" placeholder="Detailed description of the task..." class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150" rows="4"></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 transition-colors duration-200">Start Time:</label>
                                    <input type="time" id="start_time" name="start_time" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 transition-colors duration-200">End Time:</label>
                                    <input type="time" id="end_time" name="end_time" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 transition-colors duration-200">Status:</label>
                                <select id="status" name="status" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150">
                                    <option value="">Select Status</option>
                                    <option value="Completed">Completed</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Pending">Pending</option>
                                </select>
                            </div>
                        </div>

                        <div id="weeklyLogFields" class="space-y-6" style="display: none;">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white transition-colors duration-200">Weekly Log Summary</h3>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 transition-colors duration-200">Weekly Goals Achieved:</label>
                                <textarea id="weekly_goals" name="weekly_goals" placeholder="List your weekly goals and achievements..." class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150" rows="3"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 transition-colors duration-200">Key Achievements:</label>
                                <textarea id="achievements" name="achievements" placeholder="What significant things did you accomplish this week?" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150" rows="3"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 transition-colors duration-200">Challenges Faced:</label>
                                <textarea id="challenges" name="challenges" placeholder="Describe any obstacles and how you addressed them..." class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150" rows="3"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 transition-colors duration-200">Lessons Learned:</label>
                                <textarea id="lessons" name="lessons" placeholder="What new insights or skills did you gain?" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150" rows="3"></textarea>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-4">
                            <button type="reset" class="px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition duration-150">
                                <i class="fas fa-redo mr-2"></i>Reset
                            </button>
                            <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition duration-150 shadow-md">
                                <i class="fas fa-save mr-2"></i>Save Log
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Recent Logs -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl overflow-hidden transition-colors duration-200">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-green-500 to-green-600">
                    <h2 class="text-xl font-semibold text-white flex items-center">
                        <i class="fas fa-history mr-2"></i>
                        Recent Logs
                    </h2>
                </div>
                <div class="p-6">
                    <?php if ($recent_logs->num_rows > 0): ?>
                        <div class="space-y-4">
                            <?php while($row = $recent_logs->fetch_assoc()): ?>
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-2xl p-5 border border-gray-100 dark:border-gray-600 hover:shadow-md transition duration-150">
                                    <div class="flex items-center justify-between mb-3">
                                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center transition-colors duration-200">
                                            <?php if ($row['type'] == 'Daily Log'): ?>
                                                <i class="fas fa-calendar-day text-blue-500 mr-2"></i>
                                            <?php else: ?>
                                                <i class="fas fa-calendar-week text-green-500 mr-2"></i>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($row['type']); ?>
                                        </h3>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 transition-colors duration-200"><?php echo date('M d, Y', strtotime($row['timestamp'])); ?></span>
                                    </div>
                                    
                                    <?php if ($row['type'] == 'Daily Log'): ?>
                                        <div class="space-y-2">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white transition-colors duration-200"><?php echo htmlspecialchars($row['task_name']); ?></p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 transition-colors duration-200"><?php echo nl2br(htmlspecialchars($row['task_desc'])); ?></p>
                                            <div class="flex items-center justify-between text-sm">
                                                <span class="text-gray-500 dark:text-gray-400 transition-colors duration-200"><?php echo htmlspecialchars($row['start_time']); ?> - <?php echo htmlspecialchars($row['end_time']); ?></span>
                                                <span class="px-2 py-1 rounded-full text-xs font-semibold <?php 
                                                    echo $row['status'] === 'Completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                                        ($row['status'] === 'In Progress' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 
                                                        'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'); 
                                                ?> transition-colors duration-200">
                                                    <?php echo htmlspecialchars($row['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="space-y-2">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white transition-colors duration-200">Weekly Goals:</p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 transition-colors duration-200"><?php echo nl2br(htmlspecialchars($row['weekly_goals'])); ?></p>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white transition-colors duration-200">Achievements:</p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 transition-colors duration-200"><?php echo nl2br(htmlspecialchars($row['achievements'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="mt-4 flex justify-end space-x-2">
                                        <a href="edit_log.php?id=<?php echo $row['id']; ?>" class="px-3 py-1 text-sm bg-blue-50 dark:bg-blue-900 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-800 transition duration-150">
                                            <i class="fas fa-edit mr-1"></i>Edit
                                        </a>
                                        <a href="delete_log.php?id=<?php echo $row['id']; ?>" class="px-3 py-1 text-sm bg-red-50 dark:bg-red-900 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-100 dark:hover:bg-red-800 transition duration-150" onclick="return confirm('Are you sure you want to delete this log?');">
                                            <i class="fas fa-trash-alt mr-1"></i>Delete
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <i class="fas fa-clipboard-list text-gray-400 dark:text-gray-500 text-4xl mb-3 transition-colors duration-200"></i>
                            <p class="text-gray-500 dark:text-gray-400 transition-colors duration-200">No logs saved yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleLogFields() {
        var logType = document.getElementById('logType').value;
        if (logType === 'daily') {
            document.getElementById('dailyLogFields').style.display = 'block';
            document.getElementById('weeklyLogFields').style.display = 'none';
        } else {
            document.getElementById('dailyLogFields').style.display = 'none';
            document.getElementById('weeklyLogFields').style.display = 'block';
        }
    }

    // Set initial state based on default selected option
    toggleLogFields();
</script>

<?php // Optionally include a footer if you have one: ?>
<?php // include '../includes/footer.php'; ?> 