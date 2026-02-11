<?php
header('Content-Type: application/json');
require __DIR__ . '/../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || strcasecmp($_SESSION['role'], 'Admin') !== 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$version_id = $_GET['version_id'] ?? null;

// If no version specified, get Active
if (!$version_id) {
    $v_res = $conn->query("SELECT id FROM timetable_versions WHERE status = 'Active' LIMIT 1");
    if ($row = $v_res->fetch_assoc()) {
        $version_id = $row['id'];
    }
}

if (!$version_id) {
    echo json_encode(['success' => false, 'message' => 'No active timetable found to analyze.']);
    exit();
}

try {
    // Query: Get all faculties and count their entries in this version
    $sql = "SELECT 
                f.id, 
                f.name, 
                f.designation,
                d.name as dept_name,
                (SELECT COUNT(*) FROM timetable_entries te 
                 WHERE te.faculty_id = f.id AND te.version_id = ?) as total_hours
            FROM faculties f
            LEFT JOIN departments d ON f.department_id = d.id
            ORDER BY total_hours DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $version_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $data, 'version_id' => $version_id]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>