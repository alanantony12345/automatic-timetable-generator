<?php
// MOCK DATA
$allocations = [
    [
        'section_id' => 1,
        'subject_id' => 25,
        'faculty_id' => 7,
        'subject_type' => 'Theory',
        'weekly_hours' => 4
    ]
];

$days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
$periods_per_day = 7;
$rooms = [['id' => 101, 'type' => 'Lecture']]; // 1 room

// STATE
$timetable = [];
$faculty_schedule = [];
$room_schedule = [];
$section_daily_subjects = [];

echo "--- Starting Mock Generation ---\n";

foreach ($allocations as $alloc) {
    $section_id = $alloc['section_id'];
    $faculty_id = $alloc['faculty_id'];
    $subject_id = $alloc['subject_id'];
    $type = $alloc['subject_type'];
    $hours_needed = $alloc['weekly_hours'];
    $assigned_hours = 0;

    echo "Alloc: Sub-$subject_id Sec-$section_id Need-$hours_needed Type-$type\n";

    if (!isset($section_daily_subjects[$section_id])) {
        $section_daily_subjects[$section_id] = [];
    }

    foreach ($days as $day) {
        if ($assigned_hours >= $hours_needed)
            break;

        // Constraint Logic
        if ($type !== 'Lab' && isset($section_daily_subjects[$section_id][$day][$subject_id])) {
            if ($hours_needed <= count($days)) {
                echo "  -> Skipping $day (Strict 1/day)\n";
                continue;
            }
            if ($section_daily_subjects[$section_id][$day][$subject_id] >= ceil($hours_needed / count($days))) {
                echo "  -> Skipping $day (Max reached)\n";
                continue;
            }
        }

        for ($period = 1; $period <= $periods_per_day; $period++) {
            if ($assigned_hours >= $hours_needed)
                break;

            // Simplistic assignment (always success in mock)
            // ... checks ...

            // Assign
            $timetable[$day][$period][$section_id] = "Assigned";

            if (!isset($section_daily_subjects[$section_id][$day][$subject_id])) {
                $section_daily_subjects[$section_id][$day][$subject_id] = 0;
            }
            $section_daily_subjects[$section_id][$day][$subject_id] += 1; // 1 slot

            $assigned_hours++;
            echo "  -> Assigned: $day Period $period\n";

            if ($type !== 'Lab') {
                echo "  -> Breaking Period Loop\n";
                break;
            }
        }
    }
}

echo "--- End ---\n";
?>