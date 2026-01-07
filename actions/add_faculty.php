<?php
require '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../admin_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $department_id = $_POST['department_id'];
    $designation = $_POST['designation'];

    $max_hours_week = $_POST['max_hours_week'] ?? 20;

    if (empty($name) || empty($email) || empty($department_id) || empty($designation)) {
        die("Please fill in all fields.");
    }

    $stmt = $conn->prepare("INSERT INTO faculties (name, email, department_id, designation, max_hours_week) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisi", $name, $email, $department_id, $designation, $max_hours_week);

    if ($stmt->execute()) {
        header("Location: ../admin_dashboard.php?success=faculty_added");
    } else {
        header("Location: ../admin_dashboard.php?error=faculty_add_failed");
    }
    $stmt->close();
}
?>