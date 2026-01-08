<?php
header('Content-Type: application/json');
require __DIR__ . '/../config/db.php';
session_start();

ob_start();

try {
    if (!isset($_SESSION['user_id']) || strcasecmp($_SESSION['role'], 'Admin') !== 0) {
        throw new Exception("Unauthorized access.");
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create_login') {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $role = 'Faculty';

        if (empty($name) || empty($email) || empty($password)) {
            throw new Exception("Fill all fields.");
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE password = ?");
        $stmt->bind_param("sssss", $name, $email, $hashed, $role, $hashed);

        if ($stmt->execute()) {
            ob_clean();
            echo json_encode(['success' => true, 'message' => 'Login account created/updated for ' . $name]);
        } else {
            throw new Exception("Error creating user: " . $conn->error);
        }
    } else {
        throw new Exception("Invalid action.");
    }

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>