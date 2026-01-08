<?php
require __DIR__ . '/config/db.php';

echo "<h2>Departments</h2>";
$res = $conn->query("SELECT * FROM departments");
if ($res && $res->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Code</th></tr>";
    while ($row = $res->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td><td>{$row['code']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "No departments found.";
}

echo "<h2>Subjects</h2>";
$res = $conn->query("SELECT * FROM subjects");
if ($res && $res->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Code</th><th>Dept ID</th></tr>";
    while ($row = $res->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td><td>{$row['code']}</td><td>{$row['department_id']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "No subjects found.";
}
?>