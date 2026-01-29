<?php
require __DIR__ . '/config/db.php';

echo "<h2>Department Check</h2>";

// Check if departments table exists
$res = $conn->query("SHOW TABLES LIKE 'departments'");
if ($res && $res->num_rows > 0) {
    echo "<p>✅ Departments table exists</p>";

    // Count departments
    $count = $conn->query("SELECT COUNT(*) FROM departments")->fetch_row()[0];
    echo "<p>Total departments: <strong>$count</strong></p>";

    // Show all departments
    $result = $conn->query("SELECT * FROM departments");
    if ($result && $result->num_rows > 0) {
        echo "<h3>Departments in database:</h3>";
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Name</th><th>Code</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['code'] ?? '-') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ No departments found in the table!</p>";
        echo "<p>Run <a href='actions/seed_data.php'>seed_data.php</a> to add sample departments.</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Departments table does NOT exist!</p>";
    echo "<p>Run <a href='actions/fix_db_final.php'>fix_db_final.php</a> to create the table.</p>";
}
?>