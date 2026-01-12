<?php
/**
 * Database Setup for Timetable System
 * Creates all necessary tables for timetable generation, viewing, and management
 */

require __DIR__ . '/../config/db.php';

echo "<h2>Setting up Timetable Database Tables</h2>";
echo "<div style='font-family: Arial; max-width: 800px; margin: 20px;'>";

$errors = [];
$success = [];

// 1. Create timetable_versions table
$sql = "CREATE TABLE IF NOT EXISTS `timetable_versions` (
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

if ($conn->query($sql)) {
    $success[] = "✅ Table 'timetable_versions' created successfully";
} else {
    $errors[] = "❌ Error creating timetable_versions: " . $conn->error;
}

// 2. Create timetable_entries table (relational storage for viewing)
$sql = "CREATE TABLE IF NOT EXISTS `timetable_entries` (
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

if ($conn->query($sql)) {
    $success[] = "✅ Table 'timetable_entries' created successfully";
} else {
    $errors[] = "❌ Error creating timetable_entries: " . $conn->error;
}

// 3. Create timetable_conflicts table
$sql = "CREATE TABLE IF NOT EXISTS `timetable_conflicts` (
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

if ($conn->query($sql)) {
    $success[] = "✅ Table 'timetable_conflicts' created successfully";
} else {
    $errors[] = "❌ Error creating timetable_conflicts: " . $conn->error;
}

// 4. Create audit_logs table
$sql = "CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `user_role` VARCHAR(50),
    `action` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(50),
    `entity_id` INT,
    `description` TEXT,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql)) {
    $success[] = "✅ Table 'audit_logs' created successfully";
} else {
    $errors[] = "❌ Error creating audit_logs: " . $conn->error;
}

// Display results
echo "<h3 style='color: #16a34a;'>Success Messages:</h3>";
foreach ($success as $msg) {
    echo "<p style='color: #16a34a; margin: 5px 0;'>$msg</p>";
}

if (!empty($errors)) {
    echo "<h3 style='color: #dc2626;'>Error Messages:</h3>";
    foreach ($errors as $msg) {
        echo "<p style='color: #dc2626; margin: 5px 0;'>$msg</p>";
    }
}

echo "<hr>";
echo "<h3>Summary</h3>";
if (empty($errors)) {
    echo "<p style='color: #16a34a; font-weight: bold;'>✅ All timetable database tables created successfully!</p>";
    echo "<p>Your timetable system is ready to use.</p>";
} else {
    echo "<p style='color: #dc2626; font-weight: bold;'>⚠️ Some errors occurred during setup.</p>";
    echo "<p>Please fix the errors above and try again.</p>";
}

echo "</div>";
?>