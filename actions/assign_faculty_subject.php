<?php
require '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../admin_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $faculty_id = $_POST['faculty_id'];
    $subject_id = $_POST['subject_id'];

    if (empty($faculty_id) || empty($subject_id)) {
        die("Please select both faculty and subject.");
    }

    $stmt = $conn->prepare("INSERT IGNORE INTO faculty_subjects (faculty_id, subject_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $faculty_id, $subject_id);

    if ($stmt->execute()) {
        header("Location: ../admin_dashboard.php?success=assignment_added");
    } else {
        header("Location: ../admin_dashboard.php?error=assignment_failed");
    }
    $stmt->close();
}
?>