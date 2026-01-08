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
    $email = $_POST['email'] ?? '';
    $department_id = $_POST['department_id'] ?? '';
    $designation = $_POST['designation'] ?? '';
    $max_hours_week = $_POST['max_hours_week'] ?? 20;

    if (empty($name) || empty($email) || empty($department_id) || empty($designation)) {
        throw new Exception("Please fill in all required fields.");
    }

    $stmt = $conn->prepare("INSERT INTO faculties (name, email, department_id, designation, max_hours_week) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisi", $name, $email, $department_id, $designation, $max_hours_week);

    if ($stmt->execute()) {
        $new_id = $conn->insert_id;
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Faculty added successfully.',
            'faculty' => [
                'id' => $new_id,
                'name' => $name,
                'email' => $email,
                'designation' => $designation,
                'department_id' => $department_id
            ]
        ]);
    } else {
        throw new Exception("Error adding faculty: " . $conn->error);
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