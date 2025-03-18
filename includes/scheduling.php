<?php
function computeScheduling(array $patients)
{
    usort($patients, function($a, $b) {
        return $a['arrival_time'] <=> $b['arrival_time'];
    });

    $time = 0;
    $schedule = [];
    $ganttChart = [];
    $prevFinishTime = 0;
    $totalBurstTime = 0;
    $totalWaitingTime = 0;
    $totalTurnaroundTime = 0;
    $readyQueue = [];
    $completed = [];

    while (count($completed) < count($patients)) {
        // Get processes that have arrived but are not completed
        $available = array_filter($patients, function ($p) use ($time, $completed) {
            return $p['arrival_time'] <= $time && !in_array($p['name'], $completed);
        });

        if (empty($available)) {
            // If no process is available, add idle time
            $idleStart = $time;
            while (empty($available)) {
                $time++;
                $available = array_filter($patients, function ($p) use ($time, $completed) {
                    return $p['arrival_time'] <= $time && !in_array($p['name'], $completed);
                });
            }
            $idleEnd = $time;
            $ganttChart[] = ['name' => 'Idle', 'start_time' => $idleStart, 'finish_time' => $idleEnd];
        }

        // Sort available processes by priority (lower number = higher priority)
        usort($available, function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });

        // Select the process with the highest priority
        $p = array_shift($available);

        $start = max($time, $p['arrival_time']);
        $finish = $start + $p['burst_time'];
        $turnaround = $finish - $p['arrival_time'];
        $waiting = $turnaround - $p['burst_time'];

        // Save to schedule
        $schedule[] = [
            'name' => $p['name'],
            'arrival_time' => $p['arrival_time'],
            'burst_time' => $p['burst_time'],
            'priority' => $p['priority'],
            'start_time' => $start,
            'finish_time' => $finish,
            'turnaround_time' => $turnaround,
            'waiting_time' => $waiting
        ];

        // Update Gantt Chart Data
        $ganttChart[] = ['name' => $p['name'], 'start_time' => $start, 'finish_time' => $finish];

        // Update tracking
        $totalBurstTime += $p['burst_time'];
        $totalWaitingTime += $waiting;
        $totalTurnaroundTime += $turnaround;
        $prevFinishTime = $finish;
        $time = $finish;
        $completed[] = $p['name'];
    }

    $count = count($patients);
    $avgWaitingTime = $count > 0 ? $totalWaitingTime / $count : 0;
    $avgTurnaroundTime = $count > 0 ? $totalTurnaroundTime / $count : 0;
    $lastFinishTime = $prevFinishTime;

    $cpuUtilization = ($lastFinishTime > 0)
        ? ($totalBurstTime / $lastFinishTime) * 100
        : 0;

    $throughput = ($lastFinishTime > 0)
        ? $count / $lastFinishTime
        : 0;

    return [
        'schedule' => $schedule,
        'ganttChart' => $ganttChart,
        'avgWaitingTime' => $avgWaitingTime,
        'avgTurnaroundTime' => $avgTurnaroundTime,
        'cpuUtilization' => $cpuUtilization,
        'throughput' => $throughput
    ];
}
?>
