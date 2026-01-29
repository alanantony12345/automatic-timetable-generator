<?php
require '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../admin_login.php");
    exit();
}

<<<<<<< HEAD
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $id = $_SESSION['user_id'];

    if (empty($name) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
=======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $id = $_SESSION['user_id'];

    if (empty($name) || empty($email)) {
        header("Location: ../admin_dashboard.php?error=empty_fields");
>>>>>>> 5b4dce60a375ebbcc94fdc368786cc610798426a
        exit();
    }

    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
    $stmt->bind_param("ssi", $name, $email, $id);

    if ($stmt->execute()) {
        $_SESSION['user_name'] = $name; // Update session
<<<<<<< HEAD
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed.']);
=======
        header("Location: ../admin_dashboard.php?success=profile_updated");
    } else {
        header("Location: ../admin_dashboard.php?error=update_failed");
>>>>>>> 5b4dce60a375ebbcc94fdc368786cc610798426a
    }
    $stmt->close();
}
?>