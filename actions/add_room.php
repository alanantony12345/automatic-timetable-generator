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
    $type = $_POST['type'] ?? 'Lecture';
    $capacity = $_POST['capacity'] ?? '';
    $equipment = $_POST['equipment'] ?? 'Standard';

    if (empty($name) || empty($capacity)) {
        throw new Exception("Please fill in all required fields.");
    }

    $stmt = $conn->prepare("INSERT INTO classrooms (name, type, capacity, equipment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $name, $type, $capacity, $equipment);

    if ($stmt->execute()) {
        $new_id = $conn->insert_id;
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Room added successfully.',
            'room' => [
                'id' => $new_id,
                'name' => $name,
                'type' => $type,
                'capacity' => $capacity,
                'equipment' => $equipment
            ]
        ]);
    } else {
        throw new Exception("Error adding room: " . $conn->error);
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