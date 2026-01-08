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

    $name = $_POST['name'] ?? '';
    $code = $_POST['code'] ?? '';

    if (empty($name)) {
        throw new Exception("Department name is required.");
    }

    $stmt = $conn->prepare("INSERT INTO departments (name, code) VALUES (?, ?) ON DUPLICATE KEY UPDATE code = ?");
    $stmt->bind_param("sss", $name, $code, $code);

    if ($stmt->execute()) {
        $new_id = $conn->insert_id;
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Department added successfully.',
            'dept' => [
                'id' => $new_id,
                'name' => $name,
                'code' => $code
            ]
        ]);
    } else {
        throw new Exception("Error adding department: " . $conn->error);
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