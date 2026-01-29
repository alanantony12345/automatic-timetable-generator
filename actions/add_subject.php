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
    $name = trim($_POST['name'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $department_id = trim($_POST['department_id'] ?? '');
    $credits = trim($_POST['credits'] ?? '3');
    $batch_year = trim($_POST['batch_year'] ?? '');

    // New Inputs
    $academic_year = isset($_POST['academic_year']) ? (int) $_POST['academic_year'] : null;
    $semester = isset($_POST['semester']) ? (int) $_POST['semester'] : null;
    $section_id = !empty($_POST['section_id']) ? (int) $_POST['section_id'] : null;

    // Validate
    if (empty($name) || empty($code) || empty($department_id)) {
        throw new Exception('Please fill in Name, Code, and Department.');
    }

    // Validate Mapping Logic
    // If strict compliance: Course must be mapped to valid Dept, Year, Sem, (Section)
    // We check if the section exists if provided
    if ($section_id) {
        $sec_check = $conn->query("SELECT id, department_id, year, semester FROM sections WHERE id = $section_id");
        if ($sec_check->num_rows === 0) {
            throw new Exception("Invalid Section selected.");
        }
        $sec_data = $sec_check->fetch_assoc();
        if ($sec_data['department_id'] != $department_id) {
            throw new Exception("Section does not belong to the selected Department.");
        }
        // Auto-fill Year/Sem if not provided or mismatch? 
        // User likely selects Section, so we trust Section's Year/Sem
        $academic_year = $sec_data['year'];
        $semester = $sec_data['semester'];
    }

    if (empty($academic_year) || empty($semester)) {
        throw new Exception("Academic Year and Semester are required.");
    }

    // Duplicate Check
    // "Do not allow the same course... for the same department, year/semester, and section"
    $dup_sql = "SELECT id FROM subjects WHERE department_id = ? AND academic_year = ? AND semester = ? AND (name = ? OR code = ?)";
    $types = "iiiss";
    $params = [$department_id, $academic_year, $semester, $name, $code];

    if ($section_id) {
        $dup_sql .= " AND section_id = ?";
        $types .= "i";
        $params[] = $section_id;
    } else {
        // If applying to all sections (null), check if null exists? 
        // Or if we interpret "Assign only to mapped section" means Section is MANDATORY.
        // Assuming Section is optional but usually selected.
        $dup_sql .= " AND section_id IS NULL";
    }

    $check = $conn->prepare($dup_sql);
    $check->bind_param($types, ...$params);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        throw new Exception("Subject '$name' ($code) already exists for this Year/Semester/Section.");
    }

    // DB Insert
    $stmt = $conn->prepare("INSERT INTO subjects (name, code, department_id, credits, batch_year, academic_year, semester, section_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Database Prepare Error: " . $conn->error);
    }

    $stmt->bind_param("ssiisiii", $name, $code, $department_id, $credits, $batch_year, $academic_year, $semester, $section_id);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Subject added successfully.';
        $response['subject'] = [
            'id' => $conn->insert_id,
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