<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => ''];

    if ($action === 'create_login') {
        $faculty_id = $_POST['faculty_id'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $name = $_POST['name'];

        // Check if email exists in users
        $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
        if ($check->num_rows > 0) {
            $response['message'] = 'User with this email already exists.';
        } else {
            // Insert into users
            // We assume faculties table is linked via email or name, but here we just create a User entry 
            // and perhaps update the faculties table to link to this user_id if needed.
            // For simplicity, we just create the user with Role 'Faculty'.
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, is_active) VALUES (?, ?, ?, 'Faculty', 1)");
            $stmt->bind_param("sss", $name, $email, $password);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Login created successfully.';
            } else {
                $response['message'] = 'Error: ' . $conn->error;
            }
        }
    } elseif ($action === 'toggle_status') {
        $user_id = $_POST['user_id'];
        $status = $_POST['status']; // 1 or 0
        $conn->query("UPDATE users SET is_active = $status WHERE id = $user_id");
        $response['success'] = true;
        $response['message'] = 'Status updated.';
    } elseif ($action === 'reset_password') {
        $user_id = $_POST['user_id'];
        $new_pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password = '$new_pass' WHERE id = $user_id");
        $response['success'] = true;
        $response['message'] = 'Password reset successfully.';
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>