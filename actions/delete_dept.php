<?php
header('Content-Type: application/json');
require __DIR__ . '/../config/db.php';
session_start();

ob_start();

try {
    if (!isset($_SESSION['user_id']) || strcasecmp($_SESSION['role'], 'Admin') !== 0) {
        throw new Exception("Unauthorized access.");
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method.");
    }

    $id = $_POST['id'] ?? null;

    if (!$id) {
        throw new Exception("Department ID is required.");
    }

    $stmt = $conn->prepare("DELETE FROM departments WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Department deleted successfully.'
        ]);
    } else {
        throw new Exception("Error deleting department: " . $conn->error);
    }
    $stmt->close();

} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>