<?php
// Prevent any output before JSON
ob_start();
ini_set('display_errors', 0); // Log errors but don't show them
error_reporting(E_ALL);

header('Content-Type: application/json');

session_start();

// Initialize response
$response = ['success' => false, 'message' => 'An unexpected error occurred.'];

try {
    // Check Config
    if (!file_exists('../config/db.php')) {
        throw new Exception('Database configuration file not found.');
    }
    require '../config/db.php';

    // Check Auth
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || strcasecmp($_SESSION['role'], 'Admin') !== 0) {
        throw new Exception('Unauthorized access. Please login.');
    }

    // Check Method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    // Get Inputs
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $name = trim($_POST['name'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $department_id = trim($_POST['department_id'] ?? '');
    $credits = trim($_POST['credits'] ?? '3');
    $batch_year = trim($_POST['batch_year'] ?? '');

    // Optional Inputs
    $academic_year = isset($_POST['academic_year']) ? (int) $_POST['academic_year'] : null;
    $semester = isset($_POST['semester']) ? (int) $_POST['semester'] : null;
    $section_id = !empty($_POST['section_id']) ? (int) $_POST['section_id'] : null;

    if ($id <= 0) {
        throw new Exception("Invalid Subject ID.");
    }

    if (empty($name) || empty($code) || empty($department_id)) {
        throw new Exception("Name, Code and Department are required.");
    }

    // Duplicate Check (Name or Code in same Dept, excluding current ID)
    $dupStmt = $conn->prepare("SELECT id FROM subjects WHERE (name = ? OR code = ?) AND department_id = ? AND id != ?");
    $dupStmt->bind_param("ssii", $name, $code, $department_id, $id);
    $dupStmt->execute();
    $dupStmt->store_result();
    if ($dupStmt->num_rows > 0) {
        throw new Exception("Subject Name or Code already exists in this Department.");
    }
    $dupStmt->close();

    // Prepare Update
    $stmt = $conn->prepare("UPDATE subjects SET name = ?, code = ?, department_id = ?, credits = ?, batch_year = ?, academic_year = ?, semester = ?, section_id = ? WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Database Prepare Error: " . $conn->error);
    }

    $stmt->bind_param("ssiisiiii", $name, $code, $department_id, $credits, $batch_year, $academic_year, $semester, $section_id, $id);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Subject updated successfully.';
        $response['subject'] = [
            'id' => $id,
            'name' => $name,
            'code' => $code,
            'department_id' => $department_id,
            'credits' => $credits,
            'batch_year' => $batch_year,
            'academic_year' => $academic_year,
            'semester' => $semester,
            'section_id' => $section_id
        ];
    } else {
        throw new Exception("Database Execute Error: " . $stmt->error);
    }
    $stmt->close();

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

// Clear any buffered output (warnings, whitespace from includes)
ob_end_clean();

// Output JSON
echo json_encode($response);
exit;
?>