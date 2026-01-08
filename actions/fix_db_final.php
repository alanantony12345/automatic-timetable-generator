<?php
require __DIR__ . '/../config/db.php';

echo "<h2>Fixing Database Schema</h2>";

function columnExists($conn, $table, $column)
{
    $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $res && $res->num_rows > 0;
}

// 1. Fix Departments Table
$res = $conn->query("SHOW TABLES LIKE 'departments'");
if ($res && $res->num_rows > 0) {
    echo "Processing 'departments' table...<br>";

    // Rename department_id to id if exists and id doesn't
    if (columnExists($conn, 'departments', 'department_id') && !columnExists($conn, 'departments', 'id')) {
        $conn->query("ALTER TABLE departments CHANGE department_id id INT AUTO_INCREMENT");
        echo "Renamed department_id to id.<br>";
    }

    // Rename department_name to name if exists and name doesn't
    if (columnExists($conn, 'departments', 'department_name') && !columnExists($conn, 'departments', 'name')) {
        $conn->query("ALTER TABLE departments CHANGE department_name name VARCHAR(100) NOT NULL UNIQUE");
        echo "Renamed department_name to name.<br>";
    }

    // Ensure 'code' exists
    if (!columnExists($conn, 'departments', 'code')) {
        $conn->query("ALTER TABLE departments ADD COLUMN code VARCHAR(10) UNIQUE AFTER name");
        echo "Added 'code' column.<br>";
    }
} else {
    echo "Creating 'departments' table...<br>";
    $conn->query("CREATE TABLE departments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        code VARCHAR(10) UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}

// 2. Fix Subjects Table
if (columnExists($conn, 'subjects', 'department_id')) {
    echo "Subjects table department_id exists.<br>";
}

// 3. Fix Faculties Table
if (columnExists($conn, 'faculties', 'department_id')) {
    echo "Faculties table department_id exists.<br>";
}

echo "<h3>Database cleanup complete.</h3>";
?>