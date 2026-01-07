<?php
require '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../admin_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $department_id = $_POST['department_id'];
    $semester = $_POST['semester'];
    $room_id = $_POST['room_id'];

    if (empty($department_id) || empty($semester) || empty($room_id)) {
        die("Please fill in all fields.");
    }

    $stmt = $conn->prepare("INSERT INTO class_rooms (department_id, semester, room_id) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $department_id, $semester, $room_id);

    if ($stmt->execute()) {
        header("Location: ../admin_dashboard.php?success=room_assigned");
    } else {
        header("Location: ../admin_dashboard.php?error=room_assignment_failed");
    }
    $stmt->close();
}
?>