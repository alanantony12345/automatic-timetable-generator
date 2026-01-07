<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

session_start();

$response = ['success' => false, 'message' => 'An unexpected error occurred.'];

try {
    require __DIR__ . '/../config/db.php';

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || strcasecmp($_SESSION['role'], 'Admin') !== 0) {
        throw new Exception('Unauthorized access.');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    $id = $_POST['id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $credits = trim($_POST['credits'] ?? '');
    $batch_year = trim($_POST['batch_year'] ?? '');
    $department_id = trim($_POST['department_id'] ?? '');

    if (empty($id) || empty($name) || empty($code) || empty($department_id)) {
        throw new Exception('Required fields are missing.');
    }

    $stmt = $conn->prepare("UPDATE subjects SET name=?, code=?, credits=?, batch_year=?, department_id=? WHERE id=?");
    $stmt->bind_param("ssisii", $name, $code, $credits, $batch_year, $department_id, $id);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Subject updated successfully.';

        // Fetch department name for the response
        $dept_name = '';
        $dept_stmt = $conn->prepare("SELECT department_name FROM departments WHERE department_id = ?");
        $dept_stmt->bind_param("i", $department_id);
        $dept_stmt->execute();
        $dept_stmt->bind_result($dept_name);
        $dept_stmt->fetch();
        $dept_stmt->close();

        $response['subject'] = [
            'id' => $id,
            'name' => $name,
            'code' => $code,
            'credits' => $credits,
            'batch_year' => $batch_year,
            'department_id' => $department_id,
            'department_name' => $dept_name
        ];
    } else {
        throw new Exception("Database error: " . $stmt->error);
    }
    $stmt->close();

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

ob_end_clean();
echo json_encode($response);
exit;
?>