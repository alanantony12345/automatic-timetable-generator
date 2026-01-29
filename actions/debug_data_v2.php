<?php
require __DIR__ . '/../config/db.php';
header('Content-Type: text/plain');

echo "--- Faculty Subjects Schema ---\n";
$res = $conn->query("DESCRIBE faculty_subjects");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}

echo "\n--- Allocations (Joined) ---\n";
$sql = "
    SELECT 
        fs.section_id, 
        fs.subject_id, 
        s.name as sub_name,
        s.type as sub_table_type,
        fs.faculty_id,
        fs.subject_type as alloc_type,
        fs.weekly_hours
    FROM faculty_subjects fs
    JOIN subjects s ON fs.subject_id = s.id
    ORDER BY fs.section_id, fs.subject_id
";
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Error: " . $conn->error;
}
?>