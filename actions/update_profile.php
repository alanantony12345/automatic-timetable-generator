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
    $id = $_SESSION['user_id'];

    if (empty($name) || empty($email)) {
        header("Location: ../admin_dashboard.php?error=empty_fields");
        exit();
    }

    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
    $stmt->bind_param("ssi", $name, $email, $id);

    if ($stmt->execute()) {
        $_SESSION['user_name'] = $name; // Update session
        header("Location: ../admin_dashboard.php?success=profile_updated");
    } else {
        header("Location: ../admin_dashboard.php?error=update_failed");
    }
    $stmt->close();
}
?>