<?php
header('Content-Type: application/json');
require __DIR__ . '/../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || strcasecmp($_SESSION['role'], 'Admin') !== 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    // Check for latest active version
    $active_stmt = $conn->prepare("SELECT id, version_name, created_at, 'Active' as state FROM timetable_versions WHERE status = 'Active' ORDER BY created_at DESC LIMIT 1");
    $active_stmt->execute();
    $active_res = $active_stmt->get_result();
    $active = $active_res->fetch_assoc();

    // Check for latest draft
    $draft_stmt = $conn->prepare("SELECT id, version_name, created_at, 'Draft' as state FROM timetable_versions WHERE status = 'Draft' ORDER BY created_at DESC LIMIT 1");
    $draft_stmt->execute();
    $draft_res = $draft_stmt->get_result();
    $draft = $draft_res->fetch_assoc();

    echo json_encode([
        'success' => true,
        'active' => $active,
        'draft' => $draft,
        'has_active' => !empty($active),
        'has_draft' => !empty($draft)
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>