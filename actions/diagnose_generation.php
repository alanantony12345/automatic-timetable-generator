<?php
// actions/diagnose_generation.php
// PURPOSE: Run timetable generation logic with direct HTML output to catch Fatal Errors

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Timetable Generation Diagnostic</h1>";
echo "<p>Starting process...</p>";

require __DIR__ . '/../config/db.php';
echo "<p>DB Connected.</p>";

// 1. Mock Session for logic to valid
$userId = 1; // Assuming ID 1 exists
$_SESSION['user_id'] = $userId;
echo "<p>Session Mocked.</p>";

// 2. Fetch Data
try {
    // Departments
    $res = $conn->query("SELECT COUNT(*) FROM departments");
    echo "<p>Departments Check: " . ($res ? "OK" : "Failed") . "</p>";

    // Auto-heal logic (Simplified)
    $faculties = [];
    $res = $conn->query("SELECT * FROM faculties");
    if ($res)
        while ($row = $res->fetch_assoc())
            $faculties[$row['id']] = $row;
    echo "<p>Faculties Loaded: " . count($faculties) . "</p>";

    $subjects = [];
    $res = $conn->query("SELECT * FROM subjects");
    if ($res)
        while ($row = $res->fetch_assoc())
            $subjects[$row['id']] = $row;
    echo "<p>Subjects Loaded: " . count($subjects) . "</p>";

    $sections = [];
    $res = $conn->query("SELECT * FROM sections");
    if ($res)
        while ($row = $res->fetch_assoc())
            $sections[$row['id']] = $row;
    echo "<p>Sections Loaded: " . count($sections) . "</p>";

    $rooms = [];
    $res = $conn->query("SELECT * FROM classrooms");
    if ($res)
        while ($row = $res->fetch_assoc())
            $rooms[$row['id']] = $row;
    echo "<p>Rooms Loaded: " . count($rooms) . "</p>";

    $raw_allocations = [];
    $res = $conn->query("SHOW TABLES LIKE 'faculty_subjects'");
    if ($res->num_rows > 0) {
        $res = $conn->query("SELECT * FROM faculty_subjects");
        if ($res)
            while ($row = $res->fetch_assoc())
                $raw_allocations[] = $row;
    }
    echo "<p>Raw Allocations: " . count($raw_allocations) . "</p>";

    $allocations = [];
    foreach ($raw_allocations as $r) {
        if (!empty($r['section_id'])) {
            $allocations[] = $r;
        }
    }
    echo "<p>Valid Allocations: " . count($allocations) . "</p>";

    if (empty($allocations) && count($faculties) > 0 && count($subjects) > 0 && count($sections) > 0) {
        echo "<p>Generating Sample Allocations...</p>";
        foreach ($sections as $sec) {
            foreach ($subjects as $sub) {
                // Round robin
                $facIds = array_keys($faculties);
                if (empty($facIds))
                    continue;
                $randomFacId = $facIds[array_rand($facIds)];
                $allocations[] = [
                    'section_id' => $sec['id'],
                    'subject_id' => $sub['id'],
                    'faculty_id' => $randomFacId,
                    'subject_type' => 'Theory',
                    'weekly_hours' => 4
                ];
            }
        }
    }
    echo "<p>Final Allocations Count: " . count($allocations) . "</p>";

    if (empty($allocations)) {
        die("<h2>FATAL: No Allocations could be created. Missing base data.</h2>");
    }

    // Settings
    $settings = [];
    $res = $conn->query("SELECT * FROM academic_settings");
    if ($res)
        while ($row = $res->fetch_assoc())
            $settings[$row['key_name']] = $row['value'];

    $working_days_json = $settings['working_days'] ?? '["Monday","Tuesday","Wednesday","Thursday","Friday"]';
    $days = json_decode($working_days_json, true);
    if (!is_array($days))
        $days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"];
    echo "<p>Days: " . implode(", ", $days) . "</p>";

    // GENERATION LOOP
    $timetable = [];
    $faculty_schedule = [];
    $room_schedule = [];
    $conflicts = [];
    $assigned_count = 0;

    $periods_per_day = 7;

    foreach ($allocations as $alloc) {
        $section_id = $alloc['section_id'];
        $subject_id = $alloc['subject_id'];
        $faculty_id = $alloc['faculty_id'] ?? array_key_first($faculties);
        $type = $alloc['subject_type'] ?? 'Theory';
        $hours = $alloc['weekly_hours'] ?? 3;

        $assigned_hours = 0;

        foreach ($days as $day) {
            if ($assigned_hours >= $hours)
                break;
            for ($period = 1; $period <= $periods_per_day; $period++) {
                if ($assigned_hours >= $hours)
                    break;
                // Simplified assignment logic for diags
                $check_period = $period;

                // Check basic conflicts
                if (isset($timetable[$day][$check_period][$section_id]))
                    continue;
                if (isset($faculty_schedule[$faculty_id][$day][$check_period]))
                    continue;

                // Assign
                $assigned_room = array_key_first($rooms);
                $timetable[$day][$check_period][$section_id] = [
                    'subject_id' => $subject_id,
                    'faculty_id' => $faculty_id,
                    'room_id' => $assigned_room
                ];
                $faculty_schedule[$faculty_id][$day][$check_period] = true;
                $assigned_hours++;
                $assigned_count++;
            }
        }
    }

    echo "<h3>Assigned Entries: $assigned_count</h3>";

    if ($assigned_count > 0) {
        $conn->begin_transaction();
        $vName = "Diagnostic_" . date('H:i:s');
        $conn->query("INSERT INTO timetable_versions (version_name, status, created_by) VALUES ('$vName', 'Draft', $userId)");
        $vid = $conn->insert_id;
        echo "<p>Created Version ID: $vid</p>";

        $stmt = $conn->prepare("INSERT INTO timetable_entries (version_id, day, period, section_id, subject_id, faculty_id, room_id, type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        $count = 0;
        foreach ($timetable as $day => $periods) {
            foreach ($periods as $p => $secs) {
                foreach ($secs as $sid => $entry) {
                    $type = 'Theory';
                    $stmt->bind_param("isiiiiss", $vid, $day, $p, $sid, $entry['subject_id'], $entry['faculty_id'], $entry['room_id'], $type);
                    if (!$stmt->execute())
                        echo "Insert Error: " . $stmt->error . "<br>";
                    $count++;
                }
            }
        }
        $conn->commit();
        echo "<h2>SUCCESS: Saved $count entries to DB.</h2>";
    } else {
        echo "<h2>FAILED: 0 Assignments.</h2>";
    }

} catch (Exception $e) {
    echo "<h2>EXCEPTION: " . $e->getMessage() . "</h2>";
}
?>