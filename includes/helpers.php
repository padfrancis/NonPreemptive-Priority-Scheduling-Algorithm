<?php
/**
 * buildGanttBlocks
 *
 * Given a $schedule (array of patients with start_time, finish_time),
 * build an array of blocks (including Idle blocks) in chronological order.
 * Each block = [ 'name' => string, 'start' => float, 'finish' => float ]
 *
 * @param array $schedule (sorted or unsorted)
 * @return array of blocks
 */
function buildGanttBlocks(array $schedule)
{
    if (empty($schedule)) {
        return [];
    }

    // Sort by actual start_time
    $sorted = $schedule;
    usort($sorted, fn($a, $b) => $a['start_time'] <=> $b['start_time']);

    $blocks = [];
    $prevFinish = 0;

    foreach ($sorted as $entry) {
        $start  = $entry['start_time'];
        $finish = $entry['finish_time'];

        // If there's a gap => Idle
        if ($start > $prevFinish) {
            $blocks[] = [
                'name'   => 'Idle',
                'start'  => $prevFinish,
                'finish' => $start
            ];
        }

        // Process block
        $blocks[] = [
            'name'   => $entry['name'],
            'start'  => $start,
            'finish' => $finish
        ];

        $prevFinish = $finish;
    }
    return $blocks;
}
