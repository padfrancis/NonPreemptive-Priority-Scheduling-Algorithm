<?php
function buildGanttBlocks(array $schedule)
{
    if (empty($schedule)) {
        return [];
    }

    $sorted = $schedule;
    usort($sorted, fn($a, $b) => $a['start_time'] <=> $b['start_time']);

    $blocks = [];
    $prevFinish = 0;

    foreach ($sorted as $entry) {
        $start  = $entry['start_time'];
        $finish = $entry['finish_time'];

        if ($start > $prevFinish) {
            $blocks[] = [
                'name'   => 'Idle',
                'start'  => $prevFinish,
                'finish' => $start
            ];
        }

        $blocks[] = [
            'name'   => $entry['name'],
            'start'  => $start,
            'finish' => $finish
        ];

        $prevFinish = $finish;
    }
    return $blocks;
}
