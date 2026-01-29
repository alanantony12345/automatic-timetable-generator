<?php
header('Content-Type: application/json');
require __DIR__ . '/../config/db.php';
// If this outputs anything other than JSON, we have a problem in db.php or server config
echo json_encode(['status' => 'ok', 'message' => 'Clean JSON output']);
?>