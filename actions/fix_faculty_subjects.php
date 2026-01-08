<?php
require __DIR__ . '/../config/db.php';

$sqls = [
    "ALTER TABLE faculty_subjects ADD COLUMN IF NOT EXISTS type ENUM('Theory', 'Lab') DEFAULT 'Theory'",
    "ALTER TABLE faculty_subjects ADD COLUMN IF NOT EXISTS weekly_hours INT DEFAULT 4"
];

foreach ($sqls as $sql) {
    if (!$conn->query($sql)) {
        echo "Error: " . $conn->error . "<br>";
    }
}
echo "faculty_subjects table updated.";
?>