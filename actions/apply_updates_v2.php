<?php
require_once '../config/db.php';

$sqlFile = '../updates_v2.sql';
if (!file_exists($sqlFile)) {
    die("SQL file not found.");
}

$sql = file_get_contents($sqlFile);

// Remove comments and split by semicolon
$lines = explode(';', $sql);
$success = 0;
$errors = 0;

foreach ($lines as $line) {
    $line = trim($line);
    if (!empty($line)) {
        if ($conn->query($line)) {
            $success++;
        } else {
            // Ignore "Duplicate column" errors
            if ($conn->errno == 1060) {
                // Column already exists, safe to ignore
            } else {
                echo "Error executing query: " . $conn->error . "<br>Query: " . htmlspecialchars($line) . "<br><br>";
                $errors++;
            }
        }
    }
}

echo "Database update completed. Success: $success, Errors: $errors";
?>