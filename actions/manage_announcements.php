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

    if ($action === 'create') {
        $title = $_POST['title'] ?? '';
        $message = $_POST['message'] ?? '';
        $target_audience = $_POST['target_audience'] ?? 'All';

        if (empty($title) || empty($message)) {
            throw new Exception("Title and message are required.");
        }

        $stmt = $conn->prepare("INSERT INTO announcements (title, message, target_audience) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $title, $message, $target_audience);

        if ($stmt->execute()) {
            ob_clean();
            echo json_encode(['success' => true, 'message' => 'Announcement posted.']);
        } else {
            throw new Exception("Error posting announcement: " . $conn->error);
        }
    } else if ($action === 'delete') {
        $id = $_POST['id'] ?? null;
        if (!$id)
            throw new Exception("ID required.");

        $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            ob_clean();
            echo json_encode(['success' => true, 'message' => 'Announcement deleted.']);
        } else {
            throw new Exception("Error deleting announcement: " . $conn->error);
        }
    } else {
        throw new Exception("Invalid action.");
    }

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>