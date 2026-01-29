<?php
/**
 * Improved Timetable Generation System
 * Generates timetable and stores in relational database for easy viewing
 */

header('Content-Type: application/json');
require __DIR__ . '/../config/db.php';
session_start();

ob_start();

try {
    // Authentication check
    if (!isset($_SESSION['user_id']) || strcasecmp($_SESSION['role'], 'Admin') !== 0) {
        throw new Exception("Unauthorized access");
    }

    // 1. Fetch all necessary data
    $faculties = [];
    $res = $conn->query("SELECT * FROM faculties");
    while ($row = $res->fetch_assoc()) {
        $faculties[$row['id']] = $row;
    }

    $subjects = [];
    $res = $conn->query("SELECT * FROM subjects");
    while ($row = $res->fetch_assoc()) {
        $subjects[$row['id']] = $row;
    }

    $rooms = [];
    $res = $conn->query("SELECT * FROM classrooms");
    while ($row = $res->fetch_assoc()) {
        $rooms[$row['id']] = $row;
    }

    $sections = [];
    $res = $conn->query("SELECT * FROM sections");
    while ($row = $res->fetch_assoc()) {
        $sections[$row['id']] = $row;
    }

    // Check if faculty_subjects table exists and has data
    $allocations = [];
    $table_check = $conn->query("SHOW TABLES LIKE 'faculty_subjects'");
    if ($table_check && $table_check->num_rows > 0) {
        $res = $conn->query("SELECT * FROM faculty_subjects");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $allocations[] = $row;
            }
        }
    }

    // If no allocations, create sample data
    if (empty($allocations) && !empty($subjects) && !empty($faculties) && !empty($sections)) {
        // Auto-create some allocations for demonstration
        foreach ($subjects as $subject) {
            foreach ($sections as $section) {
                // Assign first available faculty to each subject-section pair
                $faculty_id = array_key_first($faculties);
                $allocations[] = [
                    'faculty_id' => $faculty_id,
                    'subject_id' => $subject['id'],
                    'section_id' => $section['id'],
                    'subject_type' => 'Theory',
                    'weekly_hours' => 3
                ];
            }
        }
    }

    // 2. Fetch Academic Settings
    $settings = [];
    $res = $conn->query("SELECT * FROM academic_settings");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $settings[$row['key_name']] = $row['value'];
        }
    }

    $periods_per_day = isset($settings['periods_per_day']) ? (int) $settings['periods_per_day'] : 7;
    $working_days_json = $settings['working_days'] ?? '["Monday","Tuesday","Wednesday","Thursday","Friday"]';
    $days = json_decode($working_days_json, true);

    // 3. Initialize timetable structure
    $timetable = [];
    $faculty_schedule = [];
    $room_schedule = [];
    $conflicts = [];

    // 4. Generate timetable using simple algorithm
    $assigned_count = 0;

    foreach ($allocations as $alloc) {
        if (empty($alloc['section_id']))
            continue;

        $section_id = $alloc['section_id'];
        $faculty_id = $alloc['faculty_id'];
        $subject_id = $alloc['subject_id'];
        $type = $alloc['subject_type'] ?? 'Theory';
        $hours_needed = $alloc['weekly_hours'] ?? 3;
        $assigned_hours = 0;

        // Try to assign slots
        foreach ($days as $day) {
            if ($assigned_hours >= $hours_needed)
                break;

            for ($period = 1; $period <= $periods_per_day; $period++) {
                if ($assigned_hours >= $hours_needed)
                    break;

                $slots_needed = ($type === 'Lab') ? 2 : 1;

                // Check if we have enough periods left in the day
                if ($period + $slots_needed - 1 > $periods_per_day)
                    continue;

                // Check availability
                $can_assign = true;
                for ($k = 0; $k < $slots_needed; $k++) {
                    $check_period = $period + $k;

                    // Check section availability
                    if (isset($timetable[$day][$check_period][$section_id])) {
                        $can_assign = false;
                        break;
                    }

                    // Check faculty availability
                    if (isset($faculty_schedule[$faculty_id][$day][$check_period])) {
                        $can_assign = false;
                        break;
                    }
                }

                if ($can_assign) {
                    // Find available room
                    $assigned_room = null;
                    foreach ($rooms as $room) {
                        $room_available = true;
                        for ($k = 0; $k < $slots_needed; $k++) {
                            $check_period = $period + $k;
                            if (isset($room_schedule[$room['id']][$day][$check_period])) {
                                $room_available = false;
                                break;
                            }
                        }

                        if ($room_available) {
                            // Prefer matching room type for labs
                            if ($type === 'Lab' && $room['type'] !== 'Lab')
                                continue;
                            $assigned_room = $room['id'];
                            break;
                        }
                    }

                    // If no room found, log conflict but still create entry
                    if (!$assigned_room && !empty($rooms)) {
                        $assigned_room = array_key_first($rooms); // Fallback to first room
                        $conflicts[] = [
                            'day' => $day,
                            'period' => $period,
                            'type' => 'Room Conflict',
                            'description' => "No suitable room available for {$type} class",
                            'severity' => 'Medium'
                        ];
                    }

                    // Assign the slot
                    for ($k = 0; $k < $slots_needed; $k++) {
                        $check_period = $period + $k;

                        $entry = [
                            'day' => $day,
                            'period' => $check_period,
                            'section_id' => $section_id,
                            'subject_id' => $subject_id,
                            'faculty_id' => $faculty_id,
                            'room_id' => $assigned_room,
                            'type' => $type
                        ];

                        $timetable[$day][$check_period][$section_id] = $entry;
                        $faculty_schedule[$faculty_id][$day][$check_period] = true;
                        if ($assigned_room) {
                            $room_schedule[$assigned_room][$day][$check_period] = true;
                        }
                    }

                    $assigned_hours += $slots_needed;
                    $assigned_count++;
                }
            }
        }
    }

    // 5. Save to database
    $conn->begin_transaction();

    // Create new version
    $version_name = "Generated_" . date('Y-m-d_H-i-s');
    $stmt = $conn->prepare("INSERT INTO timetable_versions (version_name, status, data_json, created_by) VALUES (?, 'Draft', ?, ?)");
    $data_json = json_encode($timetable);
    $stmt->bind_param("ssi", $version_name, $data_json, $_SESSION['user_id']);
    $stmt->execute();
    $version_id = $stmt->insert_id;

    // Save individual entries
    $stmt = $conn->prepare("INSERT INTO timetable_entries (version_id, day, period, section_id, subject_id, faculty_id, room_id, type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($timetable as $day => $periods) {
        foreach ($periods as $period => $sections_data) {
            foreach ($sections_data as $section_id => $entry) {
                $stmt->bind_param(
                    "isiiiis",
                    $version_id,
                    $entry['day'],
                    $entry['period'],
                    $entry['section_id'],
                    $entry['subject_id'],
                    $entry['faculty_id'],
                    $entry['room_id'],
                    $entry['type']
                );
                $stmt->execute();
            }
        }
    }

    // Save conflicts
    if (!empty($conflicts)) {
        $stmt = $conn->prepare("INSERT INTO timetable_conflicts (version_id, conflict_type, description, severity, day, period) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($conflicts as $conflict) {
            $stmt->bind_param(
                "issssi",
                $version_id,
                $conflict['type'],
                $conflict['description'],
                $conflict['severity'],
                $conflict['day'],
                $conflict['period']
            );
            $stmt->execute();
        }
    }

    $conn->commit();

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Timetable generated successfully!',
        'version_id' => $version_id,
        'version_name' => $version_name,
        'entries_count' => $assigned_count,
        'conflicts_count' => count($conflicts)
    ]);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>