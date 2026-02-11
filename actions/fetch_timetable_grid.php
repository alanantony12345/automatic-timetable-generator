<?php
/**
 * Fetch Timetable Grid API
 * Returns timetable entries for a specific version and section
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
session_start();

try {
    // 1. Auth Check
    if (!isset($_SESSION['user_id']) || strcasecmp($_SESSION['role'], 'Admin') !== 0) {
        throw new Exception("Unauthorized access");
    }

    // 2. Validate Input
    if (!isset($_GET['version_id']) || !isset($_GET['section_id'])) {
        throw new Exception("Missing required parameters (version_id, section_id)");
    }

    $version_id = (int) $_GET['version_id'];
    $section_id = (int) $_GET['section_id'];

    if ($version_id <= 0 || $section_id <= 0) {
        throw new Exception("Invalid parameters");
    }

    // 3. Fetch Allocations First (to create Subject->Faculty map)
    $alloc_sql = "
        SELECT 
            fs.subject_id,
            s.name as subject_name, 
            s.code as subject_code, 
            f.name as faculty_name, 
            fs.weekly_hours,
            fs.type
        FROM faculty_subjects fs
        JOIN subjects s ON fs.subject_id = s.id
        JOIN faculties f ON fs.faculty_id = f.id
        JOIN sections sec ON sec.id = ?
        WHERE 
            fs.section_id = ? 
            OR 
            (fs.section_id IS NULL AND s.department_id = sec.department_id)
        ORDER BY s.name ASC
    ";

    $stmt_alloc = $conn->prepare($alloc_sql);
    $allocations = [];
    $subject_faculty_map = [];

    if ($stmt_alloc) {
        $stmt_alloc->bind_param("ii", $section_id, $section_id);
        if ($stmt_alloc->execute()) {
            $alloc_res = $stmt_alloc->get_result();
            while ($ar = $alloc_res->fetch_assoc()) {
                $allocations[] = $ar;
                // Map subject_id to current faculty name
                $subject_faculty_map[$ar['subject_id']] = $ar['faculty_name'];
            }
        }
        $stmt_alloc->close();
    }

    // 4. Fetch Entries and Override Faculty Name
    $sql = "
        SELECT 
            te.day, 
            te.period, 
            te.subject_id, 
            s.name as subject_name, 
            s.code as subject_code,
            te.faculty_id, 
            f.name as faculty_name,
            te.room_id, 
            r.name as room_name,
            te.type
        FROM timetable_entries te
        LEFT JOIN subjects s ON te.subject_id = s.id
        LEFT JOIN faculties f ON te.faculty_id = f.id
        LEFT JOIN classrooms r ON te.room_id = r.id
        WHERE te.version_id = ? AND te.section_id = ?
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Query preparation failed: " . $conn->error);
    }

    $stmt->bind_param("ii", $version_id, $section_id);

    if (!$stmt->execute()) {
        throw new Exception("Query execution failed: " . $stmt->error);
    }

    $res = $stmt->get_result();
    $entries = [];

    while ($row = $res->fetch_assoc()) {
        $key = $row['day'] . '-' . $row['period'];

        // Critical Fix: Use Live Allocation if available
        $live_faculty = $subject_faculty_map[$row['subject_id']] ?? $row['faculty_name'];

        $entries[$key] = [
            'subject_id' => $row['subject_id'],
            'subject_name' => $row['subject_name'],
            'subject_code' => $row['subject_code'],
            'faculty_id' => $row['faculty_id'],
            'faculty_name' => $live_faculty, // Use overridden name
            'room_id' => $row['room_id'],
            'room_number' => $row['room_name'],
            'type' => $row['type']
        ];
    }


    echo json_encode(['success' => true, 'entries' => $entries, 'allocations' => $allocations]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>