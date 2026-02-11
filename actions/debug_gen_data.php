<?php
require __DIR__ . '/../config/db.php';

echo "<h1>Generation Data Debug</h1>";

// 1. Settings
echo "<h2>Academic Settings</h2>";
$res = $conn->query("SELECT * FROM academic_settings");
$settings = [];
if ($res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        echo "{$row['key_name']}: {$row['value']}<br>";
        $settings[$row['key_name']] = $row['value'];
    }
} else {
    echo "No academic settings found. (Using defaults)<br>";
}

// Check decoded days
$working_days_json = $settings['working_days'] ?? '["Monday","Tuesday","Wednesday","Thursday","Friday"]';
$days = json_decode($working_days_json, true);
echo "<strong>Decoded Days:</strong> ";
print_r($days);
echo "<br>";

// 2. Counts
$tables = ['faculties', 'subjects', 'sections', 'classrooms', 'faculty_subjects'];
echo "<h2>Table Counts</h2>";
foreach ($tables as $t) {
    $res = $conn->query("SELECT COUNT(*) as c FROM $t");
    $count = $res ? $res->fetch_assoc()['c'] : 'Error';
    echo "$t: $count<br>";
}

// 3. Allocations Sample
echo "<h2>Existing Allocations (faculty_subjects)</h2>";
$res = $conn->query("SELECT * FROM faculty_subjects LIMIT 5");
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        print_r($row);
        echo "<br>";
    }
} else {
    echo "No manual allocations found.<br>";
}

?>