<?php
/**
 * Fetch Faculty Timetable Grid API
 * Returns timetable entries and allocations for a specific faculty
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
session_start();

try {
    // 1. Auth Check - Allow Admin or the Faculty themselves (if logged in)
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Unauthorized access");
    }

    // 2. Validate Input
    if (!isset($_GET['version_id']) || !isset($_GET['faculty_id'])) {
        throw new Exception("Missing required parameters (version_id, faculty_id)");
    }

    $version_id = (int) $_GET['version_id'];
    $faculty_id = (int) $_GET['faculty_id'];

    if ($version_id <= 0 || $faculty_id <= 0) {
        throw new Exception("Invalid parameters");
    }

    // 3. Fetch Allocations (Subjects assigned to this faculty)
    $stmt_alloc = $conn->prepare("
        SELECT 
            s.name as subject_name, 
            s.code as subject_code, 
            sec.section_name,
            sec.year,
            sec.semester,
            d.name as dept_name,
            fs.weekly_hours,
            fs.type
        FROM faculty_subjects fs
        JOIN subjects s ON fs.subject_id = s.id
        LEFT JOIN sections sec ON fs.section_id = sec.id
        LEFT JOIN departments d ON s.department_id = d.id  -- Changed join to subject's department or allow null
        WHERE fs.faculty_id = ?
        ORDER BY s.name ASC
    ");

    $allocations = [];
    if ($stmt_alloc) {
        $stmt_alloc->bind_param("i", $faculty_id);
        if ($stmt_alloc->execute()) {
            $res = $stmt_alloc->get_result();
            while ($row = $res->fetch_assoc()) {
                $allocations[] = $row;
            }
        }
        $stmt_alloc->close();
    }

    // 4. Fetch Timetable Entries for this Faculty
    $stmt_entries = $conn->prepare("
        SELECT 
            te.day, 
            te.period, 
            s.name as subject_name, 
            s.code as subject_code,
            sec.section_name,
            sec.year,
            sec.semester,
            d.code as dept_code,
            r.name as room_name,
            te.type
        FROM timetable_entries te
        JOIN subjects s ON te.subject_id = s.id
        JOIN sections sec ON te.section_id = sec.id
        JOIN departments d ON sec.department_id = d.id
        LEFT JOIN classrooms r ON te.room_id = r.id
        WHERE te.version_id = ? AND te.faculty_id = ?
    ");

    $entries = [];
    if ($stmt_entries) {
        $stmt_entries->bind_param("ii", $version_id, $faculty_id);
        if ($stmt_entries->execute()) {
            $res = $stmt_entries->get_result();
            while ($row = $res->fetch_assoc()) {
                $key = $row['day'] . '-' . $row['period'];
                $entries[$key] = [
                    'subject_name' => $row['subject_name'],
                    'subject_code' => $row['subject_code'],
                    'section' => $row['dept_code'] . ' ' . $row['year'] . '-' . $row['section_name'], // e.g., CS 2-A
                    'room' => $row['room_name'] ?? 'N/A',
                    'type' => $row['type']
                ];
            }
        }
        $stmt_entries->close();
    }

    echo json_encode([
        'success' => true,
        'entries' => $entries,
        'allocations' => $allocations,
        'faculty_id' => $faculty_id
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>