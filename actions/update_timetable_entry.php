<?php
header('Content-Type: application/json');
require __DIR__ . '/../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || strcasecmp($_SESSION['role'], 'Admin') !== 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit();
}

$entry_id = $_POST['entry_id'] ?? null;
$subject_id = $_POST['subject_id'] ?? null;
$faculty_id = $_POST['faculty_id'] ?? null;
$room_id = $_POST['room_id'] ?? null;
$force = isset($_POST['force']) && $_POST['force'] === 'true';

if (!$entry_id || !$subject_id || !$faculty_id) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

// 1. Get Current Entry Details (Day, Period, Version ID)
$stmt = $conn->prepare("SELECT version_id, day, period, section_id FROM timetable_entries WHERE id = ?");
$stmt->bind_param("i", $entry_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Entry not found']);
    exit();
}

$current = $res->fetch_assoc();
$version_id = $current['version_id'];
$day = $current['day'];
$period = $current['period'];
$section_id = $current['section_id']; // Not changing section, just who teaches what

// 2. Conflict Checks
$conflicts = [];

// Check Faculty Availability
$f_stmt = $conn->prepare("SELECT COUNT(*) FROM timetable_entries 
                          WHERE version_id = ? AND day = ? AND period = ? 
                          AND faculty_id = ? AND id != ?");
$f_stmt->bind_param("issii", $version_id, $day, $period, $faculty_id, $entry_id);
$f_stmt->execute();
if ($f_stmt->get_result()->fetch_row()[0] > 0) {
    $conflicts[] = "Faculty is already assigned to another class at this time.";
}

// Check Room Availability
if ($room_id) {
    $r_stmt = $conn->prepare("SELECT COUNT(*) FROM timetable_entries 
                              WHERE version_id = ? AND day = ? AND period = ? 
                              AND room_id = ? AND id != ?");
    $r_stmt->bind_param("issii", $version_id, $day, $period, $room_id, $entry_id);
    $r_stmt->execute();
    if ($r_stmt->get_result()->fetch_row()[0] > 0) {
        $conflicts[] = "Room is already booked at this time.";
    }
}

// 3. Handle Conflicts
if (!empty($conflicts) && !$force) {
    echo json_encode([
        'success' => false,
        'status' => 'conflict',
        'message' => implode(" ", $conflicts),
        'conflicts' => $conflicts
    ]);
    exit();
}

// 4. Update Entry
$u_stmt = $conn->prepare("UPDATE timetable_entries SET subject_id = ?, faculty_id = ?, room_id = ? WHERE id = ?");
$u_stmt->bind_param("iiii", $subject_id, $faculty_id, $room_id, $entry_id);

if ($u_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Timetable updated successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $conn->error]);
}
?>