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

    $stmt = $conn->prepare("DELETE FROM faculty_subjects WHERE faculty_id = ? AND subject_id = ?");
    $stmt->bind_param("ii", $faculty_id, $subject_id);

    if ($stmt->execute()) {
        header("Location: ../admin_dashboard.php?success=assignment_deleted");
    } else {
        header("Location: ../admin_dashboard.php?error=delete_failed");
    }
    $stmt->close();
}
?>