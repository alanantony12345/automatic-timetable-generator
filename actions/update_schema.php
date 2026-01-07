<?php
require_once '../config/db.php';

$sql = file_get_contents('../updates.sql');

if (!$sql) {
    die("Error reading updates.sql file.");
}

// Prepare the connection for multiple queries
// mysqli_multi_query is needed for running multiple statements
if ($conn->multi_query($sql)) {
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
        // Check if there are more results
    } while ($conn->next_result());
    echo "Database schema updated successfully!";
} else {
    echo "Error updating database: " . $conn->error;
}

$conn->close();
?>