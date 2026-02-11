<?php
require __DIR__ . '/includes/config/db.php';

if ($conn) {
    echo "Connected successfully.<br>";
    $result = $conn->query("DESCRIBE departments");
    if ($result) {
        echo "Columns in 'departments' table:<br>";
        while ($row = $result->fetch_assoc()) {
            echo $row['Field'] . " - " . $row['Type'] . "<br>";
        }
    } else {
        echo "Error describing table: " . $conn->error;
    }
} else {
    echo "Connection failed.";
}
?>