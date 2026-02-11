<?php
header('Content-Type: application/json');
require __DIR__ . '/../config/db.php';
session_start();

ob_start();

try {
    if (!isset($_SESSION['user_id']) || strcasecmp($_SESSION['role'], 'Admin') !== 0) {
        throw new Exception("Unauthorized access.");
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method.");
    }

    $department_id = $_POST['department_id'] ?? '';
    $year = $_POST['year'] ?? '';
    $semester = $_POST['semester'] ?? '';
    $section_name = $_POST['section_name'] ?? '';
    $student_strength = $_POST['student_strength'] ?? 60;

    if (empty($department_id) || empty($year) || empty($semester) || empty($section_name)) {
        throw new Exception("Please fill in all required fields.");
    }


    // Check for Duplicate
    $check = $conn->prepare("SELECT id FROM sections WHERE department_id = ? AND year = ? AND semester = ? AND section_name = ?");
    $check->bind_param("iiis", $department_id, $year, $semester, $section_name);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        throw new Exception("This section mapping already exists.");
    }


    $stmt = $conn->prepare("INSERT INTO sections (department_id, year, semester, section_name, student_strength) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisi", $department_id, $year, $semester, $section_name, $student_strength);

    if ($stmt->execute()) {
        $new_id = $conn->insert_id;
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Section added successfully.',
            'section' => [
                'id' => $new_id,
                'department_id' => $department_id,
                'year' => $year,
                'semester' => $semester,
                'section_name' => $section_name,
                'student_strength' => $student_strength
            ]
        ]);
    } else {
        throw new Exception("Error adding section: " . $conn->error);
    }
    $stmt->close();

} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>