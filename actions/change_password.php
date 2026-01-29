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
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $id = $_SESSION['user_id'];

    if (empty($current) || empty($new) || empty($confirm)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
=======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    $id = $_SESSION['user_id'];

    if (empty($current) || empty($new) || empty($confirm)) {
        header("Location: ../admin_dashboard.php?error=empty_fields_pass");
>>>>>>> 5b4dce60a375ebbcc94fdc368786cc610798426a
        exit();
    }

    if ($new !== $confirm) {
<<<<<<< HEAD
        echo json_encode(['success' => false, 'message' => 'New passwords do not match.']);
=======
        header("Location: ../admin_dashboard.php?error=password_mismatch");
>>>>>>> 5b4dce60a375ebbcc94fdc368786cc610798426a
        exit();
    }

    // Verify current
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($hashed);
    $stmt->fetch();
    $stmt->close();

    if (password_verify($current, $hashed)) {
        $new_hashed = password_hash($new, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->bind_param("si", $new_hashed, $id);

        if ($update->execute()) {
<<<<<<< HEAD
            echo json_encode(['success' => true, 'message' => 'Password changed successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Incorrect current password.']);
=======
            header("Location: ../admin_dashboard.php?success=password_changed");
        } else {
            header("Location: ../admin_dashboard.php?error=db_error");
        }
    } else {
        header("Location: ../admin_dashboard.php?error=wrong_current_password");
>>>>>>> 5b4dce60a375ebbcc94fdc368786cc610798426a
    }
}
?>