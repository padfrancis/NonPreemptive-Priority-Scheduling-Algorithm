<?php
function computeScheduling(array $patients)
{
    usort($patients, function($a, $b) {
        if ($a['priority'] === $b['priority']) {
            return $a['arrival_time'] <=> $b['arrival_time'];
        }
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

        $start  = max($arrival, $prevFinishTime);
        $finish = $start + $burst;

        $turnaround = $finish - $arrival;

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

    $count = count($patients);
    $avgWaitingTime    = $count > 0 ? $totalWaitingTime / $count : 0;
    $avgTurnaroundTime = $count > 0 ? $totalTurnaroundTime / $count : 0;
    $lastFinishTime    = $prevFinishTime;

    $cpuUtilization = ($lastFinishTime > 0)
        ? ($totalBurstTime / $lastFinishTime) * 100
        : 0;

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
