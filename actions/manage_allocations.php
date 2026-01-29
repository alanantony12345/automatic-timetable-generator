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

    if ($action === 'add_allocation') {
        $faculty_ids = $_POST['faculty_id'] ?? [];
        $subject_ids = $_POST['subject_id'] ?? [];
<<<<<<< HEAD

        // Handle potential arrays from multiple rows (we take the first one for now as per legacy behavior)
        $type = $_POST['subject_type'];
        if (is_array($type))
            $type = $type[0];
        if (empty($type))
            $type = 'Theory';

        $weekly_hours = $_POST['weekly_hours'];
        if (is_array($weekly_hours))
            $weekly_hours = $weekly_hours[0];
        if (empty($weekly_hours))
            $weekly_hours = 4;

        $section_id = $_POST['section_id'] ?? null;
        if ($section_id === '')
            $section_id = null;
=======
        $type = $_POST['subject_type'] ?? 'Theory';
        $weekly_hours = $_POST['weekly_hours'] ?? 4;
>>>>>>> 5b4dce60a375ebbcc94fdc368786cc610798426a

        if (empty($faculty_ids) || empty($subject_ids)) {
            throw new Exception("Please select at least one faculty and one subject.");
        }

<<<<<<< HEAD
        foreach ($subject_ids as $sid) {
            // "Only one subject can be allocated only one faculty"
            // If section_id is present, we enforce 1:1 for that section.
            // "Only one subject can be allocated only one faculty"
            // Check if this subject is ALREADY assigned in this section
            if ($section_id) {
                $check = $conn->prepare("
                        SELECT f.name as fac_name
                        FROM faculty_subjects fs
                        JOIN faculties f ON fs.faculty_id = f.id
                        WHERE fs.subject_id = ? AND fs.section_id = ?
                    ");
                $check->bind_param("ii", $sid, $section_id);
                $check->execute();
                $res = $check->get_result();
                if ($res->num_rows > 0) {
                    $existing_fac = $res->fetch_assoc()['fac_name'];
                    throw new Exception("Validation Error: This subject is already allocated to '$existing_fac'. You cannot allocate it to another faculty.");
                }
                $check->close();
            } else {
                // Check global
                $check = $conn->prepare("SELECT f.name as fac_name FROM faculty_subjects fs JOIN faculties f ON fs.faculty_id = f.id WHERE fs.subject_id = ? AND fs.section_id IS NULL");
                $check->bind_param("i", $sid);
                $check->execute();
                $res = $check->get_result();
                if ($res->num_rows > 0) {
                    $existing_fac = $res->fetch_assoc()['fac_name'];
                    throw new Exception("Validation Error: This subject is already allocated globally to '$existing_fac'.");
                }
            }

            foreach ($faculty_ids as $fid) {
                // Insert new allocation
                $stmt = $conn->prepare("INSERT INTO faculty_subjects (faculty_id, subject_id, section_id, type, weekly_hours) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE type=?, weekly_hours=?");
                $stmt->bind_param("iiisisi", $fid, $sid, $section_id, $type, $weekly_hours, $type, $weekly_hours);
                $stmt->execute();
                $stmt->close();

                // Break after first faculty to strictly enforce single faculty selection if multiple are sent
                break;
=======
        foreach ($faculty_ids as $fid) {
            foreach ($subject_ids as $sid) {
                $stmt = $conn->prepare("INSERT INTO faculty_subjects (faculty_id, subject_id, type, weekly_hours) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE type=?, weekly_hours=?");
                $stmt->bind_param("iisiis", $fid, $sid, $type, $weekly_hours, $type, $weekly_hours);
                $stmt->execute();
                $stmt->close();
>>>>>>> 5b4dce60a375ebbcc94fdc368786cc610798426a
            }
        }

        ob_clean();
        echo json_encode(['success' => true, 'message' => 'Allocations saved successfully.']);
    } else {
        throw new Exception("Invalid action.");
    }

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>