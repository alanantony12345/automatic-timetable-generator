<?php
require '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// 1. Fetch All Necessary Data
$faculties = [];
$res = $conn->query("SELECT * FROM faculties");
while ($row = $res->fetch_assoc())
    $faculties[$row['id']] = $row;

$subjects = [];
$res = $conn->query("SELECT * FROM subjects");
while ($row = $res->fetch_assoc())
    $subjects[$row['id']] = $row;

$rooms = [];
$res = $conn->query("SELECT * FROM classrooms");
while ($row = $res->fetch_assoc())
    $rooms[$row['id']] = $row;

$sections = [];
$res = $conn->query("SELECT * FROM sections");
while ($row = $res->fetch_assoc())
    $sections[$row['id']] = $row;

$allocations = [];
$res = $conn->query("SELECT * FROM faculty_subjects");
while ($row = $res->fetch_assoc()) {
    $allocations[] = $row;
}

// 2. Fetch Academic Settings
$settings = [];
$res = $conn->query("SELECT * FROM academic_settings");
while ($row = $res->fetch_assoc())
    $settings[$row['key_name']] = $row['value'];

$periods_per_day = $settings['periods_per_day'] ?? 7;
$days = json_decode($settings['working_days'] ?? '["Monday","Tuesday","Wednesday","Thursday","Friday"]', true);

// 3. Simple Backtracking Algorithm (Simplified for V1)
// Structure: $timetable[day][period][section_id] = ['subject_id' => ..., 'faculty_id' => ..., 'room_id' => ...];

$timetable = [];
foreach ($days as $day) {
    for ($p = 1; $p <= $periods_per_day; $p++) {
        $timetable[$day][$p] = [];
    }
}

// Helper to check faculty availability (naive: strict check)
$faculty_schedule = []; // [faculty_id][day][period] = true;

// Helper to check room availability
$room_schedule = []; // [room_id][day][period] = true;

$generated_count = 0;
$conflict_count = 0;

// Iterate through allocations (Subject-Section-Faculty tuples)
foreach ($allocations as $alloc) {
    if (!$alloc['section_id'])
        continue; // Skip if not assigned to section

    $hours_needed = $alloc['weekly_hours'];
    $assigned_hours = 0;
    $section_id = $alloc['section_id'];
    $faculty_id = $alloc['faculty_id'];
    $subject_id = $alloc['subject_id'];
    $type = $alloc['subject_type']; // Theory or Lab

    // Attempt to fill hours
    foreach ($days as $day) {
        if ($assigned_hours >= $hours_needed)
            break;

        for ($p = 1; $p <= $periods_per_day; $p++) {
            if ($assigned_hours >= $hours_needed)
                break;

            // Lab handling: needs consecutive slots (e.g., 2 or 3)
            $slots_needed = ($type === 'Lab') ? 2 : 1;

            if ($p + $slots_needed - 1 > $periods_per_day)
                continue; // Not enough time left in day

            // Check availability for all needed slots
            $can_book = true;
            for ($k = 0; $k < $slots_needed; $k++) {
                $check_p = $p + $k;

                // 1. Check Section Availability
                if (isset($timetable[$day][$check_p][$section_id])) {
                    $can_book = false;
                    break;
                }
                // 2. Check Faculty Availability
                if (isset($faculty_schedule[$faculty_id][$day][$check_p])) {
                    $can_book = false;
                    break;
                }
            }

            if ($can_book) {
                // Find a Room (Naive room assignment)
                $assigned_room = null;
                foreach ($rooms as $room) {
                    $room_available = true;
                    for ($k = 0; $k < $slots_needed; $k++) {
                        $check_p = $p + $k;
                        if (isset($room_schedule[$room['id']][$day][$check_p])) {
                            $room_available = false;
                            break;
                        }
                    }
                    if ($room_available) {
                        // Check type capability (Lab vs Theory)
                        if ($type === 'Lab' && $room['type'] !== 'Lab')
                            continue;
                        //if ($type === 'Theory' && $room['type'] === 'Lab') continue; // Optional constraint

                        $assigned_room = $room['id'];
                        break;
                    }
                }

                if ($assigned_room) {
                    // Book It
                    for ($k = 0; $k < $slots_needed; $k++) {
                        $check_p = $p + $k;

                        $entry = [
                            'subject_id' => $subject_id,
                            'faculty_id' => $faculty_id,
                            'room_id' => $assigned_room,
                            'type' => $type
                        ];

                        $timetable[$day][$check_p][$section_id] = $entry;
                        $faculty_schedule[$faculty_id][$day][$check_p] = true;
                        $room_schedule[$assigned_room][$day][$check_p] = true;
                    }

                    $assigned_hours += $slots_needed; // Lab counts as X hours
                    $generated_count++;
                } else {
                    // No room found
                    $conflict_count++;
                }
            }
        }
    }
}

// 4. Save to Database
// Store as JSON for now or relational. Relational is better for viewing.
// Creating a flat export for "timetable_view" approach
$conn->query("TRUNCATE TABLE generated_timetable"); // Clear old (needs table creation)
// Or better: store strictly as JSON in a single row for this generated version
$json_data = json_encode($timetable);

// Save version
$stmt = $conn->prepare("INSERT INTO timetable_versions (version_name, data_json, created_by) VALUES (?, ?, ?)");
$v_name = "AutoGenerated_" . date('Y-m-d_H-i');
$stmt->bind_param("ssi", $v_name, $json_data, $_SESSION['user_id']);
$stmt->execute();
$version_id = $stmt->insert_id;

// Mark active
// ...

header("Location: ../admin_dashboard.php?success=generated&conflicts=$conflict_count");
?>