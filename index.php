<?php
// 1) Include session management and function libraries
require_once __DIR__ . '/includes/session_init.php';
require_once __DIR__ . '/includes/scheduling.php';
require_once __DIR__ . '/includes/helpers.php';

// 2) Check if the user clicked "Compute Scheduling"
$doCompute = isset($_POST['compute']);

// 3) Initialize variables for the scheduling results
$schedule = [];
$avgWaitingTime = 0;
$avgTurnaroundTime = 0;
$cpuUtilization = 0;
$throughput = 0;
$ganttBlocks = [];
$maxFinish = 0;

// 4) If the user requested scheduling and we have patients, compute
if ($doCompute && !empty($_SESSION['patients'])) {
    // Perform Non-Preemptive Priority Scheduling
    $result = computeScheduling($_SESSION['patients']);

    // Extract results
    $schedule           = $result['schedule'];
    $avgWaitingTime     = $result['avgWaitingTime'];
    $avgTurnaroundTime  = $result['avgTurnaroundTime'];
    $cpuUtilization     = $result['cpuUtilization'];
    $throughput         = $result['throughput'];

    // Build Gantt blocks (including idle times)
    $ganttBlocks = buildGanttBlocks($schedule);
    // Determine the maximum finish time for chart scaling
    $maxFinish = 0;
    if (!empty($ganttBlocks)) {
        $maxFinish = max(array_map(fn($b) => $b['finish'], $ganttBlocks));
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>ER Triage System</title>
    <!-- Link to your custom CSS -->
    <link rel="stylesheet" href="public/style.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<h1>ER Triage (Non-Preemptive Priority)</h1>

<!-- Add Patient Form -->
<div class="form-section">
    <h2>Add Patient</h2>
    <form method="post">
        <table class="add-patient-table">
            <tr>
                <td><label for="name">Name:</label></td>
                <td><input type="text" id="name" name="name" required></td>
            </tr>
            <tr>
                <td><label for="arrival_time">Arrival Time (sec):</label></td>
                <td><input type="number" step="0.1" id="arrival_time" name="arrival_time" required></td>
            </tr>
            <tr>
                <td><label for="burst_time">Burst Time (sec):</label></td>
                <td><input type="number" step="0.1" id="burst_time" name="burst_time" required></td>
            </tr>
            <tr>
                <td><label for="priority">Priority (higher=severe):</label></td>
                <td><input type="number" id="priority" name="priority" required></td>
            </tr>
        </table>
        <br>
        <button type="submit" name="addPatient">Add Patient</button>
    </form>
    <br>
    <form method="post">
        <button type="submit" name="clear" value="1">Clear All Patients</button>
    </form>
</div>

<!-- Current Patients Table (Unscheduled) -->
<div>
    <h2>Current Patients (Unscheduled)</h2>
    <?php if (!empty($_SESSION['patients'])): ?>
        <table class="schedule-table">
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Arrival (s)</th>
                <th>Burst (s)</th>
                <th>Priority</th>
            </tr>
            <?php foreach ($_SESSION['patients'] as $index => $p): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($p['name']); ?></td>
                    <td><?php echo $p['arrival_time']; ?></td>
                    <td><?php echo $p['burst_time']; ?></td>
                    <td><?php echo $p['priority']; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No patients in the list yet.</p>
    <?php endif; ?>
</div>

<!-- Compute Scheduling Button -->
<div style="margin-top: 20px;">
    <form method="post">
        <button type="submit" name="compute" value="1">Compute Scheduling</button>
    </form>
</div>

<!-- Display Results if computed -->
<?php if ($doCompute && !empty($schedule)): ?>
<div class="schedule-section" style="margin-top: 30px;">
    <h2>Computed Schedule & Performance Metrics</h2>
    <h3>Schedule</h3>
    <table class="schedule-table">
        <tr>
            <th>Name</th>
            <th>Arrival (s)</th>
            <th>Start (s)</th>
            <th>Burst (s)</th>
            <th>Finish (s)</th>
            <th>Turnaround (s)</th>
            <th>Waiting (s)</th>
            <th>Priority</th>
        </tr>
        <?php foreach ($schedule as $entry): ?>
        <tr>
            <td><?php echo htmlspecialchars($entry['name']); ?></td>
            <td><?php echo $entry['arrival_time']; ?></td>
            <td><?php echo $entry['start_time']; ?></td>
            <td><?php echo $entry['burst_time']; ?></td>
            <td><?php echo $entry['finish_time']; ?></td>
            <td><?php echo $entry['turnaround_time']; ?></td>
            <td><?php echo $entry['waiting_time']; ?></td>
            <td><?php echo $entry['priority']; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <h3>Performance Metrics</h3>
    <p>CPU Utilization: <?php echo number_format($cpuUtilization, 0); ?>%</p>
    <p>Throughput: <?php echo number_format($throughput, 2); ?> ms</p>
    <p>Average Turnaround Time (ATAT): <?php echo number_format($avgTurnaroundTime, 2); ?> ms</p>
    <p>Average Waiting Time (AWT): <?php echo number_format($avgWaitingTime, 2); ?> ms</p>

</div>

<!-- Single-Row Gantt Chart with Idle Times -->
<div class="chart-section">
    <h2>Gantt Chart (Single Row + Idle Times)</h2>
    <canvas id="ganttChart" width="900" height="200"></canvas>
</div>

<script src="public/script.js"></script>
<script>
    // Pass PHP data to JS
    const ganttData = <?php echo json_encode($ganttBlocks); ?>;
    const xMax      = <?php echo json_encode($maxFinish); ?>;

    // Initialize the chart
    createGanttChart('ganttChart', ganttData, xMax);
</script>
<?php endif; ?>

</body>
</html>
