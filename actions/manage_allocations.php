<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => ''];

    if ($action === 'add_allocation') {
        // Prepare arrays
        $faculty_ids = $_POST['faculty_id'] ?? [];
        $subject_ids = $_POST['subject_id'] ?? [];
        $section_ids = $_POST['section_id'] ?? [];
        $hours_list = $_POST['weekly_hours'] ?? [];
        $types = $_POST['subject_type'] ?? [];

        // Normalize to array if single value
        if (!is_array($faculty_ids))
            $faculty_ids = [$faculty_ids];
        if (!is_array($subject_ids))
            $subject_ids = [$subject_ids];

        // hours/type might come as array (legacy) or string (new). Handle both.
        $hrs = is_array($hours_list) ? ($hours_list[0] ?? 4) : ($hours_list ?: 4);
        $type = is_array($types) ? ($types[0] ?? 'Theory') : ($types ?: 'Theory');

        $success_count = 0;
        $error_count = 0;

        $stmt = $conn->prepare("INSERT INTO faculty_subjects (faculty_id, subject_id, section_id, weekly_hours, subject_type) VALUES (?, ?, ?, ?, ?)");

        // Cartesian Product Loop: M Faculties x N Subjects
        foreach ($faculty_ids as $f_id) {
            foreach ($subject_ids as $s_id) {
                if (empty($f_id) || empty($s_id))
                    continue;
                $sec_id = null; // Default null for bulk allocation

                // Check if exists
                $check_sql = "SELECT id FROM faculty_subjects WHERE faculty_id = $f_id AND subject_id = $s_id AND section_id IS NULL";
                $check = $conn->query($check_sql);

                if ($check && $check->num_rows > 0) {
                    $error_count++;
                } else {
                    $stmt->bind_param("iiids", $f_id, $s_id, $sec_id, $hrs, $type);
                    if ($stmt->execute()) {
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                }
            }
        }

        if ($success_count > 0) {
            $response['success'] = true;
            $response['message'] = "$success_count allocations added successfully." . ($error_count > 0 ? " ($error_count skipped as duplicates)" : "");
        } else {
            $response['message'] = "No allocations added. " . ($error_count > 0 ? "All combinations already exist." : "Please select at least one faculty and one subject.");
        }
    } elseif ($action === 'remove_allocation') {
        $faculty_id = $_POST['faculty_id'];
        $subject_id = $_POST['subject_id'];

        // If we want to be specific about section, we should pass it. For now, deleting general mapping
        $conn->query("DELETE FROM faculty_subjects WHERE faculty_id = $faculty_id AND subject_id = $subject_id");
        $response['success'] = true;
        $response['message'] = 'Allocation removed.';
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>