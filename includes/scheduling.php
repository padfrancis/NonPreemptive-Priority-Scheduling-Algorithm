<?php
/**
 * computeScheduling
 *
 * Perform Non-Preemptive Priority Scheduling on the given array of patients.
 * Returns an associative array containing:
 * - 'schedule'          => array of computed patient schedule
 * - 'avgWaitingTime'    => float
 * - 'avgTurnaroundTime' => float
 * - 'cpuUtilization'    => float
 * - 'throughput'        => float
 *
 * @param array $patients Array of patients, each with:
 *     [
 *       'name'         => string,
 *       'arrival_time' => float,
 *       'burst_time'   => float,
 *       'priority'     => int
 *     ]
 */
function computeScheduling(array $patients)
{
    // Sort by priority (DESC), then arrival_time (ASC)
    usort($patients, function($a, $b) {
        // If same priority, compare arrival times
        if ($a['priority'] === $b['priority']) {
            return $a['arrival_time'] <=> $b['arrival_time'];
        }
        // Sort so lower priority number = higher priority
        return $a['priority'] <=> $b['priority'];
    });
    

    $schedule = [];
    $prevFinishTime = 0;
    $totalBurstTime = 0;
    $totalWaitingTime = 0;
    $totalTurnaroundTime = 0;

    foreach ($patients as $p) {
        $arrival = $p['arrival_time'];
        $burst   = $p['burst_time'];
        $priority= $p['priority'];

        $totalBurstTime += $burst;

        // Non-preemptive scheduling
        $start  = max($arrival, $prevFinishTime);
        $finish = $start + $burst;

        // TAT = finish - arrival
        $turnaround = $finish - $arrival;
        // WT = TAT - burst
        $waiting = $turnaround - $burst;

        $schedule[] = [
            'name'            => $p['name'],
            'arrival_time'    => $arrival,
            'burst_time'      => $burst,
            'priority'        => $priority,
            'start_time'      => $start,
            'finish_time'     => $finish,
            'turnaround_time' => $turnaround,
            'waiting_time'    => $waiting
        ];

        $totalWaitingTime    += $waiting;
        $totalTurnaroundTime += $turnaround;
        $prevFinishTime       = $finish;
    }

    // Compute metrics
    $count = count($patients);
    $avgWaitingTime    = $count > 0 ? $totalWaitingTime / $count : 0;
    $avgTurnaroundTime = $count > 0 ? $totalTurnaroundTime / $count : 0;
    $lastFinishTime    = $prevFinishTime;

    // CPU Utilization = (Total Burst / Last Finish) * 100%
    $cpuUtilization = ($lastFinishTime > 0)
        ? ($totalBurstTime / $lastFinishTime) * 100
        : 0;

    // Throughput = (# of patients / Last Finish)
    $throughput = ($lastFinishTime > 0)
        ? $count / $lastFinishTime
        : 0;

    return [
        'schedule'          => $schedule,
        'avgWaitingTime'    => $avgWaitingTime,
        'avgTurnaroundTime' => $avgTurnaroundTime,
        'cpuUtilization'    => $cpuUtilization,
        'throughput'        => $throughput
    ];
}
