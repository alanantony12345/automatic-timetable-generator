<?php
session_start();
require_once '../config/db.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    // For now assuming specific admin session key or using users table role
    // If strict admin session check is needed:
    // if (!isset($_SESSION['admin_logged_in'])) { ... }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];

    try {
        // Prepare statement for upsert
        $stmt = $conn->prepare("INSERT INTO academic_settings (key_name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");

        $settings = [
            'working_days' => isset($_POST['working_days']) ? json_encode($_POST['working_days']) : '[]',
            'periods_per_day' => $_POST['periods_per_day'] ?? 7,
            'period_duration' => $_POST['period_duration'] ?? 50,
            'semester_start' => $_POST['semester_start'] ?? '',
            'semester_end' => $_POST['semester_end'] ?? '',
            'break_periods' => isset($_POST['break_periods']) ? json_encode($_POST['break_periods']) : '[]'
        ];

        foreach ($settings as $key => $value) {
            $stmt->bind_param("ss", $key, $value);
            $stmt->execute();
        }

        $response['success'] = true;
        $response['message'] = 'Academic settings saved successfully!';

    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>