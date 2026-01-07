<?php
require '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../admin_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $department_id = $_POST['department_id'];
    $year = $_POST['year'];
    $semester = $_POST['semester'];
    $section_name = $_POST['section_name'];
    $student_strength = $_POST['student_strength'] ?? 60;

    if (empty($department_id) || empty($year) || empty($semester) || empty($section_name)) {
        die("Please fill in all fields.");
    }

    $stmt = $conn->prepare("INSERT INTO sections (department_id, year, semester, section_name, student_strength) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisi", $department_id, $year, $semester, $section_name, $student_strength);

    if ($stmt->execute()) {
        header("Location: ../admin_dashboard.php?success=section_added");
    } else {
        header("Location: ../admin_dashboard.php?error=section_add_failed");
    }
    $stmt->close();
}
?>