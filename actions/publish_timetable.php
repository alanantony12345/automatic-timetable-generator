<?php
header('Content-Type: application/json');
require __DIR__ . '/../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || strcasecmp($_SESSION['role'], 'Admin') !== 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$version_id = $_POST['version_id'] ?? null;

if (!$version_id) {
    echo json_encode(['success' => false, 'message' => 'Version ID required']);
    exit();
}

try {
    $conn->begin_transaction();

    // 1. Archive current active version (optional, or just set to archived)
    $conn->query("UPDATE timetable_versions SET status = 'Archived' WHERE status = 'Active'");

    // 2. Set new version to Active
    $stmt = $conn->prepare("UPDATE timetable_versions SET status = 'Active' WHERE id = ?");
    $stmt->bind_param("i", $version_id);

    if ($stmt->execute()) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Timetable published successfully!']);
    } else {
        throw new Exception("Failed to update status.");
    }

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>