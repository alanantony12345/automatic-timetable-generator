<?php
require __DIR__ . '/../config/db.php';

// Add credits column
$sql1 = "ALTER TABLE subjects ADD COLUMN credits INT DEFAULT 3";
if ($conn->query($sql1) === TRUE) {
    echo "Column 'credits' added successfully.<br>";
} else {
    echo "Error adding 'credits' (might already exist): " . $conn->error . "<br>";
}

// Add batch_year column
$sql2 = "ALTER TABLE subjects ADD COLUMN batch_year VARCHAR(50) DEFAULT ''";
if ($conn->query($sql2) === TRUE) {
    echo "Column 'batch_year' added successfully.<br>";
} else {
    echo "Error adding 'batch_year' (might already exist): " . $conn->error . "<br>";
}

echo "Schema update check complete.";
?>