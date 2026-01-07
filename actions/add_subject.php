<?php
require '../config/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || strcasecmp($_SESSION['role'], 'Admin') !== 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please login.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $code = $_POST['code'] ?? '';
    $department_id = $_POST['department_id'] ?? '';
    $credits = $_POST['credits'] ?? 3;
    $batch_year = $_POST['batch_year'] ?? '';

    if (empty($name) || empty($code) || empty($department_id)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO subjects (name, code, department_id, credits, batch_year) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiis", $name, $code, $department_id, $credits, $batch_year);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Subject added successfully.',
            'subject' => [
                'name' => $name,
                'code' => $code,
                'department_id' => $department_id, // Ideally fetch dept name but ID is fine for MVP or we return what we sent
                'credits' => $credits,
                'batch_year' => $batch_year
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>