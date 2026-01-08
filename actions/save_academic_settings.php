<?php
header('Content-Type: application/json');
require __DIR__ . '/../config/db.php';
session_start();

ob_start();

try {
    if (!isset($_SESSION['user_id']) || strcasecmp($_SESSION['role'], 'Admin') !== 0) {
        throw new Exception("Unauthorized access.");
    }

    $settings = $_POST;

    // Process working days and break periods as JSON
    if (isset($settings['working_days'])) {
        $settings['working_days'] = json_encode($settings['working_days']);
    }
    if (isset($settings['break_periods'])) {
        // assume comma separated input if it's the single field from includes/admin_sections.html
        if (is_array($settings['break_periods'])) {
            $breaks = explode(',', $settings['break_periods'][0]);
            $settings['break_periods'] = json_encode(array_map('trim', $breaks));
        }
    }

    foreach ($settings as $key => $value) {
        if ($key === 'action')
            continue;

        // Use REPLACE INTO or INSERT ... ON DUPLICATE KEY UPDATE
        $stmt = $conn->prepare("INSERT INTO academic_settings (key_name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
        $stmt->bind_param("sss", $key, $value, $value);
        $stmt->execute();
        $stmt->close();
    }

    ob_clean();
    echo json_encode(['success' => true, 'message' => 'Academic settings updated successfully.']);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>