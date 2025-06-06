<?php
// Export Reports to PDF
require_once "../config/database.php";
require_once "../includes/auth.php";
require_once "../includes/functions.php";

// Check if user is logged in and is an admin
require_role('admin');

// Get database connection
$database = new Database();
$conn = $database->getConnection();

// Get filter parameters
$department = isset($_GET['department']) ? sanitize_input($_GET['department']) : '';
$skill_id = isset($_GET['skill_id']) ? (int)$_GET['skill_id'] : 0;
$time_period = isset($_GET['time_period']) ? (int)$_GET['time_period'] : 0;

// Get skill growth data with filters
$skill_growth_data = get_skill_growth_data($conn, $department, $skill_id, $time_period);

// Get top performing interns
$top_interns = get_top_interns($conn, 10);

// Get skill gaps
$skill_gaps = identify_skill_gaps($conn);

// Create PDF content
$html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Skill Development Tracker - Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 0;
            color: #333;
        }
        h1 {
            color: #2563eb;
            font-size: 24px;
            margin-bottom: 20px;
        }
        h2 {
            color: #4b5563;
            font-size: 18px;
            margin-top: 30px;
            margin-bottom: 15px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f3f4f6;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .progress-container {
            width: 100px;
            height: 10px;
            background-color: #e5e7eb;
            border-radius: 5px;
            overflow: hidden;
        }
        .progress-bar {
            height: 100%;
            background-color: #3b82f6;
        }
        .positive {
            color: #10b981;
        }
        .negative {
            color: #ef4444;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <h1>Skill Development Tracker - Analytics Report</h1>
    <p>Generated on: ' . date('F j, Y, g:i a') . '</p>
';

// Add filter information
$filter_info = [];
if (!empty($department)) {
    $filter_info[] = "Department: $department";
}
if (!empty($skill_id)) {
    $skill_name = '';
    foreach ($skill_growth_data as $skill) {
        if ($skill['id'] == $skill_id) {
            $skill_name = $skill['skill_name'];
            break;
        }
    }
    $filter_info[] = "Skill: $skill_name";
}
if (!empty($time_period)) {
    $filter_info[] = "Time Period: Last $time_period days";
}

if (!empty($filter_info)) {
    $html .= '<p><strong>Filters:</strong> ' . implode(', ', $filter_info) . '</p>';
}

// Skill Growth Analysis
$html .= '
    <h2>Skill Growth Analysis</h2>
';

if (empty($skill_growth_data)) {
    $html .= '<p>No skill growth data available for the selected filters.</p>';
} else {
    $html .= '
    <table>
        <thead>
            <tr>
                <th>Skill</th>
                <th>Category</th>
                <th colspan="2">Initial Level</th>
                <th colspan="2">Current Level</th>
                <th>Growth</th>
            </tr>
            <tr>
                <th></th>
                <th></th>
                <th>Beg/Int</th>
                <th>Adv/Exp</th>
                <th>Beg/Int</th>
                <th>Adv/Exp</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
    ';
    
    foreach ($skill_growth_data as $skill) {
        $initial_total = $skill['initial_beginner'] + $skill['initial_intermediate'] + $skill['initial_advanced'] + $skill['initial_expert'];
        $current_total = $skill['current_beginner'] + $skill['current_intermediate'] + $skill['current_advanced'] + $skill['current_expert'];
        
        $initial_basic = $skill['initial_beginner'] + $skill['initial_intermediate'];
        $initial_advanced = $skill['initial_advanced'] + $skill['initial_expert'];
        $current_basic = $skill['current_beginner'] + $skill['current_intermediate'];
        $current_advanced = $skill['current_advanced'] + $skill['current_expert'];
        
        $initial_advanced_percent = $initial_total > 0 ? round(($initial_advanced / $initial_total) * 100) : 0;
        $current_advanced_percent = $current_total > 0 ? round(($current_advanced / $current_total) * 100) : 0;
        
        $growth = $current_advanced_percent - $initial_advanced_percent;
        $growth_class = $growth > 0 ? 'positive' : ($growth < 0 ? 'negative' : '');
        $growth_icon = $growth > 0 ? '↑' : ($growth < 0 ? '↓' : '');
        
        $html .= '
            <tr>
                <td>' . htmlspecialchars($skill['skill_name']) . '</td>
                <td>' . ucfirst($skill['category']) . '</td>
                <td>' . $initial_basic . ' (' . ($initial_total > 0 ? round(($initial_basic / $initial_total) * 100) : 0) . '%)</td>
                <td>' . $initial_advanced . ' (' . $initial_advanced_percent . '%)</td>
                <td>' . $current_basic . ' (' . ($current_total > 0 ? round(($current_basic / $current_total) * 100) : 0) . '%)</td>
                <td>' . $current_advanced . ' (' . $current_advanced_percent . '%)</td>
                <td class="' . $growth_class . '">' . ($growth > 0 ? '+' : '') . $growth . '% ' . $growth_icon . '</td>
            </tr>
        ';
    }
    
    $html .= '
        </tbody>
    </table>
    ';
}

// Top Performing Interns
$html .= '
    <h2>Top Performing Interns</h2>
';

if (empty($top_interns)) {
    $html .= '<p>No intern performance data available yet.</p>';
} else {
    $html .= '
    <table>
        <thead>
            <tr>
                <th>Intern</th>
                <th>Department</th>
                <th>Advanced Skills</th>
                <th>Proficiency</th>
            </tr>
        </thead>
        <tbody>
    ';
    
    foreach ($top_interns as $intern) {
        $html .= '
            <tr>
                <td>' . htmlspecialchars($intern['first_name'] . ' ' . $intern['last_name']) . '</td>
                <td>' . htmlspecialchars($intern['department']) . '</td>
                <td>' . $intern['advanced_skills'] . '/' . $intern['total_skills'] . '</td>
                <td>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: ' . $intern['proficiency_percentage'] . '%;"></div>
                    </div>
                    ' . round($intern['proficiency_percentage']) . '%
                </td>
            </tr>
        ';
    }
    
    $html .= '
        </tbody>
    </table>
    ';
}

// Skills with Largest Gaps
$html .= '
    <h2>Skills with Largest Gaps</h2>
';

if (empty($skill_gaps)) {
    $html .= '<p>No skill gap data available yet.</p>';
} else {
    $html .= '
    <table>
        <thead>
            <tr>
                <th>Skill</th>
                <th>Category</th>
                <th>Beginners</th>
                <th>Percentage</th>
            </tr>
        </thead>
        <tbody>
    ';
    
    foreach (array_slice($skill_gaps, 0, 10) as $skill) {
        $html .= '
            <tr>
                <td>' . htmlspecialchars($skill['name']) . '</td>
                <td>' . ucfirst($skill['category']) . '</td>
                <td>' . $skill['beginners'] . '</td>
                <td>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: ' . $skill['beginner_percentage'] . '%;"></div>
                    </div>
                    ' . round($skill['beginner_percentage']) . '%
                </td>
            </tr>
        ';
    }
    
    $html .= '
        </tbody>
    </table>
    ';
}

// Footer
$html .= '
    <div class="footer">
        <p>This report was generated by Skill Development Tracker. Copyright &copy; ' . date('Y') . '</p>
    </div>
</body>
</html>
';

// Set PDF filename
$filename = 'Skill_Development_Report_' . date('Y-m-d') . '.pdf';

// Set headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Create simple HTML to PDF conversion using PHP's output buffering
// Note: In a real-world scenario, you would use a proper PDF library like TCPDF, FPDF, or Dompdf
// For this demo, we're outputting HTML that can be printed to PDF by the browser

echo $html;
exit;
?>
