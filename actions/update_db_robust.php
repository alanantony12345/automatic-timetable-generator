<?php
require_once '../config/db.php';

$sqlFile = file_get_contents('../updates.sql');
$queries = explode(';', $sqlFile);

foreach ($queries as $query) {
    $query = trim($query);
    if (!empty($query)) {
        if ($conn->query($query)) {
            echo "Success: " . substr($query, 0, 50) . "...\n";
        } else {
            echo "Error: " . $conn->error . " | Query: " . substr($query, 0, 50) . "...\n";
        }
    }
}

$conn->close();
?>