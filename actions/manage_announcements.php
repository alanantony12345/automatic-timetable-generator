<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
    $response = ['success' => false, 'message' => ''];

    if ($action === 'create') {
        $title = $_POST['title'] ?? '';
        $message = $_POST['message'] ?? '';
        $audience = $_POST['target_audience'] ?? 'All';

        $stmt = $conn->prepare("INSERT INTO announcements (title, message, target_audience) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $title, $message, $audience);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Announcement published!';
        } else {
            $response['message'] = 'Error: ' . $conn->error;
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        $conn->query("DELETE FROM announcements WHERE id = $id");
        $response['success'] = true;
    }

    header('Content-Type: application/json');
    echo json_encode($response);
}
?>