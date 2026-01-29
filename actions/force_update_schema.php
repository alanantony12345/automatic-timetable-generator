<?php
require __DIR__ . '/../config/db.php';

// Force Add Status Column
$sql = "ALTER TABLE `timetable_versions` ADD COLUMN `status` ENUM('Draft', 'Active', 'Archived') DEFAULT 'Draft'";

if ($conn->query($sql)) {
    echo "<h1>Success</h1><p>Column 'status' added successfully.</p>";
} else {
    echo "<h1>Error</h1><p>" . $conn->error . "</p>";
    // Check if it already exists slightly differently maybe?
}

// Add Index if not exists
$conn->query("ALTER TABLE `timetable_versions` ADD INDEX `idx_status` (`status`)");
?>