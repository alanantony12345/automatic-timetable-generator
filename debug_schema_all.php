<?php
require __DIR__ . '/config/db.php';

$tables = ['departments', 'subjects', 'faculties', 'sections', 'classrooms'];

foreach ($tables as $table) {
    echo "<h2>Schema for $table</h2>";
    $res = $conn->query("DESCRIBE $table");
    if ($res) {
        echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $res->fetch_assoc()) {
            echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td><td>{$row['Extra']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "Table $table not found or error: " . $conn->error;
    }
}
?>