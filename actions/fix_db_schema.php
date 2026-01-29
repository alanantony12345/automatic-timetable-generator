<?php
<<<<<<< HEAD
// Try to reset opcache if possible
if (function_exists('opcache_reset')) {
    opcache_reset();
}

require __DIR__ . '/../config/db.php';

echo "Starting DB Fix...<br>";

try {
    // Disable FK Checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    echo "FK Checks Disabled.<br>";

    // Drop Dependent Tables
    $conn->query("DROP TABLE IF EXISTS timetable_conflicts");
    echo "Dropped timetable_conflicts.<br>";

    $conn->query("DROP TABLE IF EXISTS timetable_entries");
    echo "Dropped timetable_entries.<br>";

    $conn->query("DROP TABLE IF EXISTS timetable_versions");
    echo "Dropped timetable_versions.<br>";

    // Enable FK Checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    echo "FK Checks Enabled.<br>";

    echo "Tables dropped successfully. <br>Re-running setup...<br>";

    // Manual Re-creation to be safe and explicit
    // 1. Version Table
    $sql = "CREATE TABLE `timetable_versions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `version_name` VARCHAR(100) NOT NULL,
        `department_id` INT DEFAULT NULL,
        `academic_year` VARCHAR(20) DEFAULT NULL,
        `semester` VARCHAR(20) DEFAULT NULL,
        `status` ENUM('Draft', 'Active', 'Archived') DEFAULT 'Draft',
        `data_json` LONGTEXT,
        `created_by` INT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_status` (`status`),
        INDEX `idx_dept` (`department_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($sql);
    echo "Created timetable_versions.<br>";

    // 2. Entries Table
    $sql = "CREATE TABLE `timetable_entries` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `version_id` INT NOT NULL,
        `day` VARCHAR(20) NOT NULL,
        `period` INT NOT NULL,
        `section_id` INT NOT NULL,
        `subject_id` INT NOT NULL,
        `faculty_id` INT NOT NULL,
        `room_id` INT DEFAULT NULL,
        `type` ENUM('Theory', 'Lab', 'Tutorial') DEFAULT 'Theory',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`version_id`) REFERENCES `timetable_versions`(`id`) ON DELETE CASCADE,
        INDEX `idx_version` (`version_id`),
        INDEX `idx_section` (`section_id`),
        INDEX `idx_faculty` (`faculty_id`),
        INDEX `idx_day_period` (`day`, `period`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($sql);
    echo "Created timetable_entries.<br>";

    // 3. Conflicts Table
    $sql = "CREATE TABLE `timetable_conflicts` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `version_id` INT NOT NULL,
        `conflict_type` VARCHAR(50) NOT NULL,
        `description` TEXT,
        `severity` ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
        `resolved` TINYINT(1) DEFAULT 0,
        `day` VARCHAR(20),
        `period` INT,
        `entity_type` VARCHAR(50),
        `entity_id` INT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`version_id`) REFERENCES `timetable_versions`(`id`) ON DELETE CASCADE,
        INDEX `idx_version` (`version_id`),
        INDEX `idx_resolved` (`resolved`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($sql);
    echo "Created timetable_conflicts.<br>";

    echo "<h1>✅ Database Fixed Successfully</h1>";

} catch (Exception $e) {
    echo "<h1>❌ Error</h1>" . $e->getMessage();
}
=======
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
>>>>>>> 5b4dce60a375ebbcc94fdc368786cc610798426a
?>