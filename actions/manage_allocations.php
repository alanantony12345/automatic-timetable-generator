<?php
header('Content-Type: application/json');
require __DIR__ . '/../config/db.php';
session_start();

ob_start();

try {
    if (!isset($_SESSION['user_id']) || strcasecmp($_SESSION['role'], 'Admin') !== 0) {
        throw new Exception("Unauthorized access.");
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add_allocation') {
        $faculty_ids = $_POST['faculty_id'] ?? [];
        $subject_ids = $_POST['subject_id'] ?? [];
        $type = $_POST['subject_type'] ?? 'Theory';
        $weekly_hours = $_POST['weekly_hours'] ?? 4;

        if (empty($faculty_ids) || empty($subject_ids)) {
            throw new Exception("Please select at least one faculty and one subject.");
        }

        foreach ($faculty_ids as $fid) {
            foreach ($subject_ids as $sid) {
                $stmt = $conn->prepare("INSERT INTO faculty_subjects (faculty_id, subject_id, type, weekly_hours) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE type=?, weekly_hours=?");
                $stmt->bind_param("iisiis", $fid, $sid, $type, $weekly_hours, $type, $weekly_hours);
                $stmt->execute();
                $stmt->close();
            }
        }

        ob_clean();
        echo json_encode(['success' => true, 'message' => 'Allocations saved successfully.']);
    } else {
        throw new Exception("Invalid action.");
    }

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>