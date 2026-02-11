<?php
require __DIR__ . '/../config/db.php';

echo "<h1>Timetable Entries Check</h1>";

// 1. Check Versions
echo "<h2>Versions</h2>";
$res = $conn->query("SELECT * FROM timetable_versions ORDER BY id DESC LIMIT 5");
if ($res->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Status</th><th>Created At</th></tr>";
    while ($row = $res->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['version_name']}</td><td>{$row['status']}</td><td>{$row['created_at']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "No versions found.<br>";
}

// 2. Check Entries Count per Version
echo "<h2>Entries Count per Version</h2>";
$sql = "SELECT version_id, COUNT(*) as count FROM timetable_entries GROUP BY version_id";
$res = $conn->query($sql);
if ($res->num_rows > 0) {
    echo "<table border='1'><tr><th>Version ID</th><th>Entry Count</th></tr>";
    while ($row = $res->fetch_assoc()) {
        echo "<tr><td>{$row['version_id']}</td><td>{$row['count']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "No entries found in DB.<br>";
}

// 3. Check Entries for specific Section if any
echo "<h2>Sample Entries</h2>";
$res = $conn->query("SELECT * FROM timetable_entries LIMIT 10");
if ($res->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Version</th><th>Day</th><th>Period</th><th>Section ID</th><th>Subject</th><th>Faculty</th><th>Room</th></tr>";
    while ($row = $res->fetch_assoc()) {
        echo "<tr>
            <td>{$row['id']}</td>
            <td>{$row['version_id']}</td>
            <td>{$row['day']}</td>
            <td>{$row['period']}</td>
            <td>{$row['section_id']}</td>
            <td>{$row['subject_id']}</td>
            <td>{$row['faculty_id']}</td>
            <td>{$row['room_id']}</td>
        </tr>";
    }
    echo "</table>";
}
?>