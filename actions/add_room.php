<?php
require '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../admin_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $type = $_POST['type'];
    $capacity = $_POST['capacity'];

    $equipment = $_POST['equipment'] ?? '';

    if (empty($name) || empty($type) || empty($capacity)) {
        die("Please fill in all fields.");
    }

    $stmt = $conn->prepare("INSERT INTO classrooms (name, type, capacity, equipment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $name, $type, $capacity, $equipment);

    if ($stmt->execute()) {
        header("Location: ../admin_dashboard.php?success=room_added");
    } else {
        header("Location: ../admin_dashboard.php?error=room_add_failed");
    }
    $stmt->close();
}
?>