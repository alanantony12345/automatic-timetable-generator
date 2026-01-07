<?php
require '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../admin_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];

    if (empty($id)) {
        die("Invalid ID.");
    }

    $stmt = $conn->prepare("DELETE FROM classrooms WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: ../admin_dashboard.php?success=room_deleted");
    } else {
        header("Location: ../admin_dashboard.php?error=room_delete_failed");
    }
    $stmt->close();
}
?>