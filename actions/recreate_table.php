<?php
require __DIR__ . '/../config/db.php';

// Disable FK Checks
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// Drop Dependent Tables
$conn->query("DROP TABLE IF EXISTS timetable_conflicts");
$conn->query("DROP TABLE IF EXISTS timetable_entries");
$conn->query("DROP TABLE IF EXISTS timetable_versions");

// Enable FK Checks
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

echo "Tables dropped. Re-running setup...<br>";

// Run Setup Script
require __DIR__ . '/setup_timetable_tables.php';
?>