<?php
require_once __DIR__ . '/includes/session_init.php';
require_once __DIR__ . '/includes/scheduling.php';
require_once __DIR__ . '/includes/helpers.php';

$doCompute = isset($_POST['compute']);

$schedule = [];
$avgWaitingTime = 0;
$avgTurnaroundTime = 0;
$cpuUtilization = 0;
$throughput = 0;
$ganttBlocks = [];
$maxFinish = 0;

if ($doCompute && !empty($_SESSION['patients'])) {
    $result = computeScheduling($_SESSION['patients']);
    $schedule           = $result['schedule'];
    $avgWaitingTime     = $result['avgWaitingTime'];
    $avgTurnaroundTime  = $result['avgTurnaroundTime'];
    $cpuUtilization     = $result['cpuUtilization'];
    $throughput         = $result['throughput'];

    $ganttBlocks = buildGanttBlocks($schedule);
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
    <link rel="stylesheet" href="public/style.css">
    <script src="../nps/public/chart.js"></script>
</head>
<body>
<h1>ER Triage System (Non-Preemptive Priority)</h1>

<div class="container">
    <div class="patient-input">
        <h2>Add Patient</h2>
        <form method="post">
            <table class="add-patient-table">
                <tr>
                    <td><label for="name">Name:</label></td>
                    <td><input type="text" id="name" name="name" required></td>
                </tr>
                <tr>
                    <td><label for="arrival_time">Arrival Time (ms):</label></td>
                    <td><input type="number" step="0.1" id="arrival_time" name="arrival_time" required></td>
                </tr>
                <tr>
                    <td><label for="burst_time">Burst Time (ms):</label></td>
                    <td><input type="number" step="0.1" id="burst_time" name="burst_time" required></td>
                </tr>
                <tr>
                    <td><label for="priority">Priority (lower = higher priority):</label></td>
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
        <br>
        <form method="post">
            <button type="submit" name="compute" value="1">Compute Scheduling</button>
        </form>
    </div>

    <div class="current-patients">
        <h2>Current Patients</h2>
        <?php if (!empty($_SESSION['patients'])): ?>
            <table class="schedule-table">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Arrival (ms)</th>
                    <th>Burst (ms)</th>
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
</div>

<?php if ($doCompute && !empty($schedule)): ?>

<div class="schedule-section" style="margin-top: 30px;">
    <h2>Ready Queue</h2>
    <?php 
      $readyQueue = $schedule;
      usort($readyQueue, fn($a, $b) => $a['start_time'] <=> $b['start_time']);
    ?>
    <table class="schedule-table">
        <tr>
            <th>Order</th>
            <th>Name</th>
            <th>Arrival (ms)</th>
            <th>Burst (ms)</th>
            <th>Priority</th>
        </tr>
        <?php foreach ($readyQueue as $index => $entry): ?>
            <tr>
                <td><?php echo $index + 1; ?></td>
                <td><?php echo htmlspecialchars($entry['name']); ?></td>
                <td><?php echo $entry['arrival_time']; ?></td>
                <td><?php echo $entry['burst_time']; ?></td>
                <td><?php echo $entry['priority']; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<div class="chart-section">
    <h2>Gantt Chart</h2>
    <canvas id="ganttChart" width="900" height="200"></canvas>
</div>

<div class="schedule-section" style="margin-top: 30px;">
    <h2>Computed Schedule & Performance Metrics</h2>
    <h3>Schedule</h3>
    <table class="schedule-table">
        <tr>
            <th>Name</th>
            <th>Arrival (ms)</th>
            <th>Start (ms)</th>
            <th>Burst (ms)</th>
            <th>Finish (ms)</th>
            <th>Turnaround (ms)</th>
            <th>Waiting (ms)</th>
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

<script src="public/script.js"></script>
<script>
    const ganttData = <?php echo json_encode($ganttBlocks); ?>;
    const xMax      = <?php echo json_encode($maxFinish); ?>;
    createGanttChart('ganttChart', ganttData, xMax);
</script>
<?php endif; ?>

</body>
</html>
