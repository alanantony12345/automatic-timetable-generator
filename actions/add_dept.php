<?php
require '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../admin_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    // Code column does not exist in DB schema

    if (empty($name)) {
        die("Please fill in all fields.");
    }

    $stmt = $conn->prepare("INSERT INTO departments (department_name) VALUES (?)");
    $stmt->bind_param("s", $name);

    if ($stmt->execute()) {
        header("Location: ../admin_dashboard.php?success=dept_added");
    } else {
        header("Location: ../admin_dashboard.php?error=dept_add_failed");
    }
    $stmt->close();
}
?>