<?php
/**
 * Improved Timetable Generation System
 * Generates timetable and stores in relational database for easy viewing
 */

header('Content-Type: application/json');
require __DIR__ . '/../config/db.php';
session_start();

ob_start(); // Start buffering immediately

$logFile = __DIR__ . '/gen_debug.log';
// Use @ to suppress potential permission warnings that break JSON
@file_put_contents($logFile, "Starting Generation at " . date('Y-m-d H:i:s') . "\n");

function logGen($msg)
{
    global $logFile;
    @file_put_contents($logFile, $msg . "\n", FILE_APPEND);
}

// ob_start(); // Already started above

try {
    // Authentication check
    if (!isset($_SESSION['user_id']) || strcasecmp($_SESSION['role'], 'Admin') !== 0) {
        throw new Exception("Unauthorized access");
    }

    // 0. AUTO-HEAL: Ensure basic data exists (Safely)
    try {
        // Check Departments
        $res = $conn->query("SELECT COUNT(*) FROM departments");
        if ($res && $res->fetch_row()[0] == 0) {
            $conn->query("INSERT INTO departments (name, code) VALUES ('General Department', 'GEN')");
        }
        $dept_res = $conn->query("SELECT id FROM departments LIMIT 1");

        if ($dept_res && $dept_res->num_rows > 0) {
            $dept_id = $dept_res->fetch_assoc()['id'];

            // Check Subjects
            $chk = $conn->query("SELECT COUNT(*) FROM subjects")->fetch_row()[0];
            if ($chk == 0) {
                $conn->query("INSERT INTO subjects (name, code, credit_score, type) VALUES ('General Mathematics', 'GEN101', 3, 'Theory')");
                $conn->query("INSERT INTO subjects (name, code, credit_score, type) VALUES ('General Science', 'GEN102', 3, 'Theory')");
            }

            // Check Rooms
            $chk = $conn->query("SELECT COUNT(*) FROM classrooms")->fetch_row()[0];
            if ($chk == 0) {
                $conn->query("INSERT INTO classrooms (name, capacity, type) VALUES ('Room 101', 60, 'Lecture')");
            }

            // Check Sections
            $chk = $conn->query("SELECT COUNT(*) FROM sections")->fetch_row()[0];
            if ($chk == 0) {
                $conn->query("INSERT INTO sections (section_name, department_id, semester, academic_year) VALUES ('Section A', $dept_id, 1, '2025-2026')");
            }

            // Check Faculties
            $chk = $conn->query("SELECT COUNT(*) FROM faculties")->fetch_row()[0];
            if ($chk == 0) {
                $conn->query("INSERT INTO faculties (name, email, department_id, designation) VALUES ('Default Faculty', 'faculty@test.com', $dept_id, 'Professor')");
            }
        }
    } catch (Exception $e) {
        logGen("Auto-Heal Warning: " . $e->getMessage());
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

    $raw_allocations = [];
    $table_check = $conn->query("SHOW TABLES LIKE 'faculty_subjects'");
    if ($table_check && $table_check->num_rows > 0) {
        $res = $conn->query("SELECT * FROM faculty_subjects");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $raw_allocations[] = $row;
            }
        }
    }

    // Filter out invalid allocations
    $allocations = [];
    foreach ($raw_allocations as $r) {
        if (!empty($r['section_id'])) {
            $allocations[] = $r;
        }
    }

    // 5. If strictly no allocations, generate sample mappings based on rules
    if (empty($allocations)) {
        logGen("No valid allocations found in DB. Generating sample (Round Robin).");
        if (!empty($subjects) && !empty($sections) && !empty($faculties)) {
            foreach ($sections as $sec) {
                foreach ($subjects as $sub) {
                    $facIds = array_keys($faculties);
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
    }

    if (empty($allocations)) {
        throw new Exception("No subject-faculty-section allocations found. Please add them in 'Course Management'.");
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
    if (!is_array($days) || empty($days)) {
        $days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"];
    }

    // 3. Initialize timetable structure
    $timetable = [];
    $faculty_schedule = [];
    $room_schedule = [];
    $conflicts = [];
    $section_daily_subjects = []; // Tracker for avoiding duplicates

    // 4. Generate timetable using Day-First Randomization Algorithm
    $assigned_count = 0;

    logGen("Allocations count: " . count($allocations));
    logGen("Periods/Day: $periods_per_day");

    // Pre-calculate remaining hours for each allocation
    $alloc_state = [];
    foreach ($allocations as $k => $alloc) {
        $alloc_state[$k] = [
            'id' => $alloc['id'] ?? $k, // Fallback if no ID
            'remaining' => $alloc['weekly_hours'] ?? 4,
            'data' => $alloc
        ];
    }

    // Iterate Days first to ensure variety across the week
    foreach ($days as $day) {
        logGen("Processing Day: $day");

        // Get list of active allocations that still need hours
        $daily_queue = [];
        foreach ($alloc_state as $k => $state) {
            if ($state['remaining'] > 0) {
                $daily_queue[] = $k;
            }
        }

        // SHUFFLE the queue differently for each day
        // This ensures Subject A isn't always 1st period.
        shuffle($daily_queue);

        foreach ($daily_queue as $k) {
            $alloc = $alloc_state[$k]['data'];
            $key = $k;

            // Extract vars
            $section_id = $alloc['section_id'];
            $faculty_id = $alloc['faculty_id'] ?? array_key_first($faculties);
            $subject_id = $alloc['subject_id'];
            $type = $alloc['subject_type'] ?? 'Theory';
            $slots_needed = ($type === 'Lab') ? 2 : 1;

            // Check Daily Limits (Distribute Logic)
            if (!isset($section_daily_subjects[$section_id])) {
                $section_daily_subjects[$section_id] = [];
            }

            // Skip if already assigned today (Strict 1/day unless overload needed)
            if (isset($section_daily_subjects[$section_id][$day][$subject_id])) {
                $daily_limit = ($alloc['weekly_hours'] > count($days)) ? ceil($alloc['weekly_hours'] / count($days)) : 1;

                // Labs usually just once per day (block of 2 or 3)
                if ($type === 'Lab')
                    $daily_limit = 1;

                if ($section_daily_subjects[$section_id][$day][$subject_id] >= $daily_limit) {
                    continue; // Next subject
                }
            }

            // Find Slot
            for ($period = 1; $period <= $periods_per_day; $period++) {

                // Bounds check
                if ($period + $slots_needed - 1 > $periods_per_day)
                    continue;

                // Check conflicts for ALL slots needed
                $can_assign = true;
                for ($s = 0; $s < $slots_needed; $s++) {
                    $p = $period + $s;
                    // Section busy?
                    if (isset($timetable[$day][$p][$section_id])) {
                        $can_assign = false;
                        break;
                    }
                    // Faculty busy?
                    if (isset($faculty_schedule[$faculty_id][$day][$p])) {
                        $can_assign = false;
                        break;
                    }
                }

                if ($can_assign) {
                    // Room Check
                    $assigned_room = null;
                    if (!empty($rooms)) {
                        foreach ($rooms as $room) {
                            $room_ok = true;
                            // Check room availability for all slots
                            for ($s = 0; $s < $slots_needed; $s++) {
                                $p = $period + $s;
                                if (isset($room_schedule[$room['id']][$day][$p])) {
                                    $room_ok = false;
                                    break;
                                }
                            }

                            if ($room_ok) {
                                // Type compatibility
                                if ($type === 'Lab' && $room['type'] !== 'Lab')
                                    continue;
                                // Ideally check Theory room type too, but let's assume loose matching for now

                                $assigned_room = $room['id'];
                                break;
                            }
                        }
                    }

                    // Fallback Room
                    if (!$assigned_room && !empty($rooms)) {
                        $assigned_room = array_key_first($rooms);
                        $conflicts[] = [
                            'day' => $day,
                            'period' => $period,
                            'type' => 'Room Conflict',
                            'description' => "Forced room share for Section $section_id",
                            'severity' => 'Medium'
                        ];
                    }

                    // Assign ALL slots
                    for ($s = 0; $s < $slots_needed; $s++) {
                        $p = $period + $s;

                        $entry = [
                            'day' => $day,
                            'period' => $p,
                            'section_id' => $section_id,
                            'subject_id' => $subject_id,
                            'faculty_id' => $faculty_id,
                            'room_id' => $assigned_room,
                            'type' => $type
                        ];

                        $timetable[$day][$p][$section_id] = $entry;
                        $faculty_schedule[$faculty_id][$day][$p] = true;
                        if ($assigned_room) {
                            $room_schedule[$assigned_room][$day][$p] = true;
                        }
                    }

                    // Update State
                    $alloc_state[$key]['remaining'] -= $slots_needed;
                    $assigned_count += $slots_needed;

                    if (!isset($section_daily_subjects[$section_id][$day][$subject_id])) {
                        $section_daily_subjects[$section_id][$day][$subject_id] = 0;
                    }
                    $section_daily_subjects[$section_id][$day][$subject_id]++; // Count *assignments* (blocks), not just hours? 
                    // Actually logic above checks COUNT >= Limit. A Lab block is 1 assignment. 
                    // But wait, if limit is hours based?
                    // Let's stick to "Assignments count" for simpler logic: 1 Theory Session or 1 Lab Session per day

                    logGen("  -> Assigned Sec-$section_id / Sub-$subject_id on $day P$period ($type)");

                    // Done for this subject on this day (unless it needs 2 separate sessions which is rare)
                    break;
                }
            }
        }
    }
    logGen("Finished Generation. Total Assigned: $assigned_count");

    // 4.1 FINAL VALIDATION PASS (Double Check)
    logGen("Running Final Validation Pass...");
    foreach ($timetable as $day => $periods) {
        foreach ($periods as $period => $sections_data) {
            $period_faculties = [];
            $period_rooms = [];

            foreach ($sections_data as $sec_id => $entry) {
                // Check Faculty Double Booking
                if (isset($period_faculties[$entry['faculty_id']])) {
                    $conflicts[] = [
                        'day' => $day,
                        'period' => $period,
                        'type' => 'Critical: Faculty Double Booking',
                        'description' => "Faculty {$entry['faculty_id']} assigned to multiple sections",
                        'severity' => 'High'
                    ];
                }
                $period_faculties[$entry['faculty_id']] = true;

                // Check Room Double Booking
                if ($entry['room_id']) {
                    if (isset($period_rooms[$entry['room_id']])) {
                        $conflicts[] = [
                            'day' => $day,
                            'period' => $period,
                            'type' => 'Critical: Room Collision',
                            'description' => "Room {$entry['room_id']} assigned to multiple sections",
                            'severity' => 'High'
                        ];
                    }
                    $period_rooms[$entry['room_id']] = true;
                }
            }
        }
    }

    if ($assigned_count === 0) {
        throw new Exception("Generation failed: No classes could be assigned. Check consistency of academic settings (Working Days) vs Allocations.");
    }

    // 5. Save to database
    $conn->begin_transaction();

    $version_name = "Generated_" . date('Y-m-d_H-i-s');
    $stmt = $conn->prepare("INSERT INTO timetable_versions (version_name, status, data_json, created_by) VALUES (?, 'Draft', ?, ?)");
    $data_json = json_encode($timetable);
    $stmt->bind_param("ssi", $version_name, $data_json, $_SESSION['user_id']);
    if (!$stmt->execute()) {
        throw new Exception("Version Insert Failed: " . $stmt->error);
    }
    $version_id = $stmt->insert_id;

    $stmt = $conn->prepare("INSERT INTO timetable_entries (version_id, day, period, section_id, subject_id, faculty_id, room_id, type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($timetable as $day => $periods) {
        foreach ($periods as $period => $sections_data) {
            foreach ($sections_data as $section_id => $entry) {
                $stmt->bind_param("isiiiiis", $version_id, $entry['day'], $entry['period'], $entry['section_id'], $entry['subject_id'], $entry['faculty_id'], $entry['room_id'], $entry['type']);
                if (!$stmt->execute()) {
                    throw new Exception("Entry Insert Failed: " . $stmt->error);
                }
            }
        }
    }

    if (!empty($conflicts)) {
        $stmt = $conn->prepare("INSERT INTO timetable_conflicts (version_id, conflict_type, description, severity, day, period) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($conflicts as $conflict) {
            $stmt->bind_param("issssi", $version_id, $conflict['type'], $conflict['description'], $conflict['severity'], $conflict['day'], $conflict['period']);
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
    if (isset($conn))
        $conn->rollback();
    ob_end_clean(); // Ensure no partial output
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>